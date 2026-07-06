<?php
$page_title = "AI Tutor";
$user = get_user_by_id($_SESSION["user_id"]);
$chats = get_ai_chats($_SESSION["user_id"]);
$current_chat_id = (int) ($_GET["chat_id"] ?? 0);
$languages = get_languages();
$messages = [];
if ($current_chat_id) {
    $messages = get_ai_messages($current_chat_id, $_SESSION["user_id"]);
}
?>
<div style="display:flex; gap:20px; height: calc(100vh - 200px); min-height: 500px; animation: fade-in 0.5s ease-out;">
    <!-- Chat Sidebar -->
    <div class="tw-card" style="width: 280px; flex-shrink:0; display:flex; flex-direction:column;">
        <div class="tw-card-header" style="flex-shrink:0;">
            <h2 class="font-bold flex items-center gap-2">
                <i class="fas fa-robot" style="color:#9147FF;"></i>
                AI Tutor
            </h2>
            <span class="live-dot"></span>
        </div>

        <div style="padding:12px; flex-shrink:0;">
            <form method="POST" class="space-y-2">
                <select name="language" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm">
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?= $lang["slug"] ?>"><?= $lang[
    "name"
] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="new_chat" class="tw-btn tw-btn-primary tw-btn-sm tw-btn-block">
                    <i class="fas fa-plus"></i>
                    New Chat
                </button>
            </form>
        </div>

        <div style="flex:1; overflow-y:auto; padding:0 12px 12px;">
            <?php if (!empty($chats)): ?>
                <?php foreach ($chats as $chat): ?>
                    <a href="index.php?page=ai-tutor&chat_id=<?= $chat["id"] ?>"
                       class="tw-nav-item <?= $chat["id"] == $current_chat_id
                           ? "active"
                           : "" ?>"
                       style="margin-bottom:4px;">
                        <i class="fas fa-comment" style="font-size:12px;"></i>
                        <div style="flex:1; overflow:hidden;">
                            <div style="font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(
                                $chat["title"],
                            ) ?></div>
                            <div style="font-size:10px; color:#ADADB8;">
                                <?= $chat["last_message"]
                                    ? substr(
                                            htmlspecialchars(
                                                $chat["last_message"],
                                            ),
                                            0,
                                            40,
                                        ) . "..."
                                    : "No messages" ?>
                            </div>
                        </div>
                        <a href="?page=ai-tutor&delete_chat=<?= $chat[
                            "id"
                        ] ?>" onclick="return confirm('Delete this chat?')" style="color:#ADADB8; font-size:12px;">
                            <i class="fas fa-times"></i>
                        </a>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:20px; color:#ADADB8; font-size:13px;">
                    <i class="fas fa-comment-dots" style="font-size:32px; margin-bottom:8px; color:#2D2D35;"></i>
                    <p>No chats yet. Start a new conversation!</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="padding:12px; border-top:1px solid #2D2D35; text-align:center; font-size:10px; color:#ADADB8;">
            Powered by Big Pickle AI
        </div>
    </div>

    <!-- Chat Area -->
    <div class="tw-card" style="flex:1; display:flex; flex-direction:column;">
        <?php if ($current_chat_id > 0): ?>
            <!-- Messages -->
            <div id="chat-messages" style="flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px;">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div style="display:flex; gap:12px; <?= $msg["role"] ===
                        "user"
                            ? "flex-direction:row-reverse;"
                            : "" ?> animation: slide-up 0.3s ease-out;">
                            <div class="tw-avatar" style="width:36px; height:36px; font-size:12px; <?= $msg[
                                "role"
                            ] === "user"
                                ? "background:linear-gradient(135deg, #FF6B35, #E9197B);"
                                : "background:linear-gradient(135deg, #9147FF, #772CE8);" ?> flex-shrink:0;">
                                <?= $msg["role"] === "user" ? "U" : "AI" ?>
                            </div>
                            <div style="max-width:80%; <?= $msg["role"] ===
                            "user"
                                ? "text-align:right;"
                                : "" ?>">
                                <div class="tw-card" style="display:inline-block; padding:12px 16px; <?= $msg[
                                    "role"
                                ] === "user"
                                    ? "background:#2D2D35;"
                                    : "" ?>">
                                    <div style="font-size:13px; line-height:1.6;"><?= render_markdown(
                                        $msg["content"],
                                    ) ?></div>
                                </div>
                                <div style="font-size:10px; color:#ADADB8; margin-top:4px; <?= $msg[
                                    "role"
                                ] === "user"
                                    ? "text-align:right;"
                                    : "" ?>">
                                    <?= time_ago($msg["created_at"]) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Welcome Message -->
                    <div style="flex:1; display:flex; align-items:center; justify-content:center; text-align:center; padding:40px;">
                        <div>
                            <div class="tw-logo" style="width:80px; height:80px; font-size:36px; margin:0 auto 20px;">AI</div>
                            <h2 class="text-2xl font-bold mb-2 gradient-text">Big Pickle AI Tutor</h2>
                            <p class="text-twitch-muted mb-6 max-w-md mx-auto">
                                Your personal coding assistant! Ask me anything about programming,
                                debug your code, or get explanations of complex concepts.
                            </p>
                            <div class="grid grid-cols-2 gap-3 max-w-sm mx-auto">
                                <div class="tw-card tw-card-body" style="cursor:pointer; text-align:center; font-size:12px;" onclick="sendSuggestion('Explain variables')">
                                    <i class="fas fa-code" style="color:#9147FF;"></i>
                                    <div>Explain variables</div>
                                </div>
                                <div class="tw-card tw-card-body" style="cursor:pointer; text-align:center; font-size:12px;" onclick="sendSuggestion('Help debug')">
                                    <i class="fas fa-bug" style="color:#E9197B;"></i>
                                    <div>Help debug</div>
                                </div>
                                <div class="tw-card tw-card-body" style="cursor:pointer; text-align:center; font-size:12px;" onclick="sendSuggestion('Show example')">
                                    <i class="fas fa-file-code" style="color:#00D95A;"></i>
                                    <div>Show example</div>
                                </div>
                                <div class="tw-card tw-card-body" style="cursor:pointer; text-align:center; font-size:12px;" onclick="sendSuggestion('Best practices')">
                                    <i class="fas fa-book" style="color:#FF6B35;"></i>
                                    <div>Best practices</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <div style="padding:16px; border-top:1px solid #2D2D35;">
                <form id="chat-form" style="display:flex; gap:12px;" onsubmit="sendMessage(event)">
                    <input type="text" id="message-input" class="flex-1 p-3 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text focus:outline-none focus:border-twitch-purple" placeholder="Ask the AI tutor anything..." autocomplete="off">
                    <button type="submit" class="tw-btn tw-btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Send
                    </button>
                </form>
                <div style="font-size:10px; color:#ADADB8; margin-top:6px; text-align:center;">
                    Big Pickle can make mistakes. Verify important code with tests. · +<?= XP_AI_TUTOR_QUESTION ?> XP per question
                </div>
            </div>
        <?php else: ?>
            <!-- Select a chat -->
            <div style="flex:1; display:flex; align-items:center; justify-content:center; text-align:center; padding:40px; color:#ADADB8;">
                <div>
                    <i class="fas fa-comment-dots" style="font-size:64px; margin-bottom:16px; color:#2D2D35;"></i>
                    <h3 class="text-xl font-bold mb-2">Select or Start a Chat</h3>
                    <p class="text-sm">Choose a conversation from the left or create a new one!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
#chat-messages::-webkit-scrollbar { width: 4px; }
#chat-messages::-webkit-scrollbar-thumb { background: #3A3A45; border-radius: 2px; }

@media (max-width: 768px) {
    div[style*="height: calc(100vh - 200px)"] {
        flex-direction: column;
        height: auto !important;
    }
    div[style*="width: 280px"] {
        width: 100% !important;
    }
}
</style>

<script>
let chatId = <?= $current_chat_id ?: "null" ?>;
let isSending = false;

function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const message = input.value.trim();

    if (!message || isSending) return;

    // Add user message to UI immediately
    addMessage('user', message);
    input.value = '';
    isSending = true;

    // Show typing indicator
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typing-indicator';
    typingDiv.style.cssText = 'display:flex; gap:12px; animation: slide-up 0.3s ease-out;';
    typingDiv.innerHTML = `
        <div class="tw-avatar" style="width:36px; height:36px; font-size:12px; background:linear-gradient(135deg, #9147FF, #772CE8); flex-shrink:0;">AI</div>
        <div style="max-width:80%;">
            <div class="tw-card" style="display:inline-block; padding:12px 16px;">
                <div class="tw-spinner"></div>
            </div>
        </div>
    `;
    document.getElementById('chat-messages').appendChild(typingDiv);
    scrollToBottom();

    // Send to server via AJAX
    const formData = new FormData();
    formData.append('message', message);
    formData.append('chat_id', chatId || 0);
    formData.append('language', document.querySelector('select[name="language"]')?.value || 'rust');

    fetch('index.php?page=ai-tutor', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('typing-indicator')?.remove();

        if (data.success) {
            // Update chat_id if a new chat was created
            if (data.chat_id && data.chat_id !== chatId) {
                chatId = data.chat_id;
                window.history.replaceState({}, '', 'index.php?page=ai-tutor&chat_id=' + chatId);
                loadChatList();
            }
            addMessage('assistant', data.rendered || data.response);
            if (data.xp_earned) {
                showToast(`+${data.xp_earned} XP`, 'success');
            }
        } else {
            addMessage('assistant', data.error || 'Sorry, I encountered an error. Please try again!');
            showToast('Failed to get response', 'error');
        }
    })
    .catch(err => {
        document.getElementById('typing-indicator')?.remove();
        addMessage('assistant', 'Network error. Please check your connection.');
        showToast('Network error', 'error');
    })
    .finally(() => {
        isSending = false;
    });
}

function addMessage(role, content) {
    const container = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.style.cssText = `display:flex; gap:12px; ${role === 'user' ? 'flex-direction:row-reverse;' : ''} animation: slide-up 0.3s ease-out;`;

    div.innerHTML = `
        <div class="tw-avatar" style="width:36px; height:36px; font-size:12px; ${role === 'user' ? 'background:linear-gradient(135deg, #FF6B35, #E9197B);' : 'background:linear-gradient(135deg, #9147FF, #772CE8);'} flex-shrink:0;">
            ${role === 'user' ? 'U' : 'AI'}
        </div>
        <div style="max-width:80%; ${role === 'user' ? 'text-align:right;' : ''}">
            <div class="tw-card" style="display:inline-block; padding:12px 16px; ${role === 'user' ? 'background:#2D2D35;' : ''}">
                <div style="font-size:13px; line-height:1.6;">${content}</div>
            </div>
        </div>
    `;

    // Remove welcome message if present
    const welcome = container.querySelector('div[style*="flex:1; display:flex; align-items:center"]');
    if (welcome) welcome.remove();

    container.appendChild(div);
    scrollToBottom();
}

function sendSuggestion(text) {
    const input = document.getElementById('message-input');
    if (input) {
        input.value = text;
        document.getElementById('chat-form')?.dispatchEvent(new Event('submit'));
    }
}

function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    setTimeout(() => {
        container.scrollTop = container.scrollHeight;
    }, 100);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadChatList() {
    fetch('index.php?page=ai-tutor&ajax_chats=1')
        .then(res => res.text())
        .then(html => {
            // Reload the page to refresh chat list (simple approach)
            location.reload();
        })
        .catch(() => {});
}

// Auto-scroll on load
document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>
