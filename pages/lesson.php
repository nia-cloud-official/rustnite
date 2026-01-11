<?php
$lesson_id = (int)($_GET['id'] ?? 0);
$lesson = get_lesson_by_id($lesson_id);

if (!$lesson) {
    header('Location: index.php?page=lessons');
    exit;
}

$is_completed = is_lesson_completed($_SESSION['user_id'], $lesson_id);

// Get user's previous code submission
$stmt = $pdo->prepare("SELECT code_submitted FROM user_progress WHERE user_id = ? AND lesson_id = ?");
$stmt->execute([$_SESSION['user_id'], $lesson_id]);
$previous_code = $stmt->fetchColumn() ?: $lesson['code_template'];

// Handle lesson completion
$result_message = '';
$result_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_code'])) {
    $submitted_code = $_POST['code'] ?? '';
    
    if (!empty($submitted_code)) {
        $result = complete_lesson($_SESSION['user_id'], $lesson_id, $submitted_code);
        
        if ($result) {
            $result_message = "🎉 Congratulations! You earned {$result['xp_earned']} XP!";
            if ($result['level_up']) {
                $result_message .= " 🚀 Level up! You're now Level {$result['level_up']}!";
            }
            $result_type = 'success';
            $is_completed = true;
        } else {
            $result_message = "Code submitted successfully! Keep practicing to improve.";
            $result_type = 'info';
        }
    } else {
        $result_message = "Please write some code before submitting!";
        $result_type = 'error';
    }
}

// Get navigation (previous/next lessons)
$stmt = $pdo->prepare("SELECT id, title FROM lessons WHERE order_num < ? ORDER BY order_num DESC LIMIT 1");
$stmt->execute([$lesson['order_num']]);
$prev_lesson = $stmt->fetch();

$stmt = $pdo->prepare("SELECT id, title FROM lessons WHERE order_num > ? ORDER BY order_num ASC LIMIT 1");
$stmt->execute([$lesson['order_num']]);
$next_lesson = $stmt->fetch();

// Get lesson progress in this difficulty
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN up.completed = 1 THEN 1 ELSE 0 END) as completed
    FROM lessons l 
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
    WHERE l.difficulty = ?
");
$stmt->execute([$_SESSION['user_id'], $lesson['difficulty']]);
$difficulty_progress = $stmt->fetch();
?>

<div class="max-w-7xl mx-auto">
    <!-- Lesson Header -->
    <div class="content-card mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <a href="index.php?page=lessons" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Lessons
                </a>
                
                <div class="flex items-center space-x-2">
                    <span class="text-xs bg-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-500/20 text-<?= $lesson['difficulty'] === 'beginner' ? 'green' : ($lesson['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-400 px-3 py-1 rounded-full">
                        <?= ucfirst($lesson['difficulty']) ?>
                    </span>
                    <span class="text-sm text-muted">
                        <i class="fas fa-star"></i> <?= $lesson['xp_reward'] ?> XP
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <?php if ($is_completed): ?>
                    <div class="bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i>
                        Completed
                    </div>
                <?php endif; ?>
                
                <div class="text-sm text-muted">
                    <?= $difficulty_progress['completed'] ?>/<?= $difficulty_progress['total'] ?> 
                    <?= ucfirst($lesson['difficulty']) ?> lessons
                </div>
            </div>
        </div>
        
        <div>
            <h1 class="title-large mb-4"><?= htmlspecialchars($lesson['title']) ?></h1>
            <p class="text-secondary"><?= htmlspecialchars($lesson['description']) ?></p>
        </div>
    </div>

    <!-- Result Message -->
    <?php if ($result_message): ?>
        <div class="content-card mb-6 <?= $result_type === 'success' ? 'border-green-500 bg-green-500/10' : ($result_type === 'error' ? 'border-red-500 bg-red-500/10' : 'border-blue-500 bg-blue-500/10') ?>">
            <div class="text-<?= $result_type === 'success' ? 'green' : ($result_type === 'error' ? 'red' : 'blue') ?>-400 font-medium">
                <?= $result_message ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Lesson Content -->
        <div class="space-y-6">
            <div class="content-card">
                <h2 class="title-medium mb-4">Lesson Content</h2>
                <div class="prose prose-invert max-w-none">
                    <div class="text-secondary leading-relaxed whitespace-pre-line"><?= htmlspecialchars($lesson['content']) ?></div>
                </div>
                
                <?php if ($lesson['expected_output']): ?>
                    <div class="mt-6 p-4 bg-gray-800/50 rounded-lg border border-gray-700">
                        <h3 class="font-bold mb-3 text-green-400">
                            <i class="fas fa-terminal mr-2"></i>
                            Expected Output:
                        </h3>
                        <pre class="text-green-300 text-sm font-mono bg-gray-900 p-3 rounded border"><?= htmlspecialchars($lesson['expected_output']) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Navigation -->
            <div class="content-card">
                <h3 class="font-bold mb-4">Navigation</h3>
                <div class="flex items-center justify-between">
                    <?php if ($prev_lesson): ?>
                        <a href="index.php?page=lesson&id=<?= $prev_lesson['id'] ?>" 
                           class="btn-secondary flex items-center">
                            <i class="fas fa-chevron-left mr-2"></i>
                            <div class="text-left">
                                <div class="text-xs text-muted">Previous</div>
                                <div class="font-medium"><?= htmlspecialchars(substr($prev_lesson['title'], 0, 30)) ?>...</div>
                            </div>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="index.php?page=lesson&id=<?= $next_lesson['id'] ?>" 
                           class="btn-primary flex items-center">
                            <div class="text-right">
                                <div class="text-xs">Next</div>
                                <div class="font-medium"><?= htmlspecialchars(substr($next_lesson['title'], 0, 30)) ?>...</div>
                            </div>
                            <i class="fas fa-chevron-right ml-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=lessons" class="btn-primary">
                            <i class="fas fa-list mr-2"></i>
                            All Lessons
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Code Editor -->
        <div class="content-card">
            <h2 class="title-medium mb-4">Code Editor</h2>
            
            <form method="POST" id="lesson-form">
                <div class="mb-4">
                    <div id="code-editor" class="border border-gray-700 rounded-lg overflow-hidden" style="height: 400px;"></div>
                    <textarea name="code" id="code-textarea" class="hidden"><?= htmlspecialchars($previous_code) ?></textarea>
                </div>
                
                <div class="flex items-center justify-between mb-4">
                    <div class="flex space-x-3">
                        <button type="button" onclick="runCode()" 
                                class="btn-secondary">
                            <i class="fas fa-play mr-2"></i>
                            Test Run
                        </button>
                        
                        <button type="button" onclick="resetCode()" 
                                class="btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            Reset
                        </button>
                        
                        <button type="button" onclick="formatCode()" 
                                class="btn-secondary">
                            <i class="fas fa-magic mr-2"></i>
                            Format
                        </button>
                    </div>
                    
                    <button type="submit" name="submit_code" 
                            class="btn-primary">
                        <i class="fas fa-check mr-2"></i>
                        <?= $is_completed ? 'Resubmit' : 'Submit Solution' ?>
                    </button>
                </div>
            </form>
            
            <!-- Output Area -->
            <div id="output-area" class="mt-6 p-4 bg-gray-900 rounded-lg border border-gray-700 hidden">
                <h3 class="font-bold mb-3 text-blue-400">
                    <i class="fas fa-terminal mr-2"></i>
                    Output:
                </h3>
                <pre id="output-content" class="text-sm text-gray-300 font-mono"></pre>
            </div>
            
            <!-- Hints -->
            <div class="mt-6 p-4 bg-blue-900/20 rounded-lg border border-blue-500/30">
                <h3 class="font-bold mb-2 text-blue-400">
                    <i class="fas fa-lightbulb mr-2"></i>
                    Tips:
                </h3>
                <ul class="text-sm text-secondary space-y-1">
                    <li>• Use <code class="bg-gray-800 px-1 rounded">Ctrl+S</code> to save your progress</li>
                    <li>• Press <code class="bg-gray-800 px-1 rounded">Ctrl+Enter</code> to run your code</li>
                    <li>• Check the expected output above for guidance</li>
                    <li>• Don't worry about getting it perfect - learning is iterative!</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
let editor;
const originalCode = `<?= addslashes($lesson['code_template'] ?? '') ?>`;
const storageKey = 'rustnite_lesson_<?= $lesson_id ?>';

// Initialize Monaco Editor
require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor = monaco.editor.create(document.getElementById('code-editor'), {
        value: `<?= addslashes($previous_code) ?>`,
        language: 'rust',
        theme: 'vs-dark',
        fontSize: 14,
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        wordWrap: 'on',
        lineNumbers: 'on',
        folding: true,
        selectOnLineNumbers: true,
        roundedSelection: false,
        readOnly: false,
        cursorStyle: 'line',
        glyphMargin: true,
        contextmenu: true,
        mouseWheelZoom: true,
        smoothScrolling: true,
        cursorBlinking: 'blink',
        cursorSmoothCaretAnimation: true,
        renderLineHighlight: 'line',
        renderWhitespace: 'selection'
    });
    
    // Auto-save functionality
    editor.onDidChangeModelContent(() => {
        const code = editor.getValue();
        localStorage.setItem(storageKey, code);
        document.getElementById('code-textarea').value = code;
    });
    
    // Keyboard shortcuts
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, runCode);
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function() {
        // Auto-save is already handled above
        showNotification('Code saved!', 'success');
    });
});

async function runCode() {
    const code = editor.getValue();
    const outputArea = document.getElementById('output-area');
    const outputContent = document.getElementById('output-content');
    
    outputArea.classList.remove('hidden');
    outputContent.textContent = 'Compiling and running your Rust code...\n';
    
    try {
        const response = await fetch('api/execute.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                code: code,
                lesson_id: <?= $lesson_id ?>
            })
        });
        
        const result = await response.json();
        
        if (result.error) {
            outputContent.textContent = 'Error: ' + result.error;
            outputContent.style.color = '#EF4444';
        } else {
            let output = '';
            
            if (result.stderr && result.stderr.trim()) {
                output += '=== Compilation Errors ===\n';
                output += result.stderr + '\n\n';
                outputContent.style.color = '#EF4444';
            } else {
                outputContent.style.color = '#10B981';
            }
            
            if (result.output && result.output.trim()) {
                if (result.stderr && result.stderr.trim()) {
                    output += '=== Program Output ===\n';
                }
                output += result.output;
            }
            
            if (result.execution_time) {
                output += '\n--- Execution Info ---\n';
                output += 'Execution time: ' + result.execution_time + '\n';
            }
            
            outputContent.textContent = output || 'No output generated.';
        }
    } catch (error) {
        outputContent.textContent = 'Network error: ' + error.message;
        outputContent.style.color = '#EF4444';
    }
}

function resetCode() {
    if (confirm('Are you sure you want to reset your code to the original template?')) {
        editor.setValue(originalCode);
        localStorage.removeItem(storageKey);
        showNotification('Code reset to template', 'info');
    }
}

function formatCode() {
    editor.getAction('editor.action.formatDocument').run();
    showNotification('Code formatted!', 'success');
}

// Load saved code on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedCode = localStorage.getItem(storageKey);
    if (savedCode && !<?= $is_completed ? 'true' : 'false' ?>) {
        // Only load saved code if lesson isn't completed
        setTimeout(() => {
            if (editor) {
                editor.setValue(savedCode);
            }
        }, 1000);
    }
    
    // Clear saved code on successful submission
    <?php if ($result_type === 'success'): ?>
    localStorage.removeItem(storageKey);
    <?php endif; ?>
});

// Form submission handler
document.getElementById('lesson-form').addEventListener('submit', function(e) {
    document.getElementById('code-textarea').value = editor.getValue();
});

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 120px;
        background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>