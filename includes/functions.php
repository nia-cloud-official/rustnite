<?php
// ============== SECURITY & SANITIZATION ==============

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function json_response($data, $code = 200)
{
    http_response_code($code);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit();
}

// ============== USER FUNCTIONS ==============

function get_user_by_id($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_level($xp)
{
    return floor($xp / XP_PER_LEVEL) + 1;
}

function get_xp_for_next_level($current_xp)
{
    $current_level = get_user_level($current_xp);
    return $current_level * XP_PER_LEVEL;
}

function update_user_activity($user_id)
{
    global $pdo;
    $today = date("Y-m-d");

    $stmt = $pdo->prepare(
        "SELECT last_activity_date, current_streak, longest_streak FROM users WHERE id = ?",
    );
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $new_streak = $user["current_streak"];

        if ($user["last_activity_date"] === $yesterday) {
            $new_streak++;
        } elseif ($user["last_activity_date"] !== $today) {
            $new_streak = 1;
        }

        $longest = max($user["longest_streak"], $new_streak);

        $stmt = $pdo->prepare("
            UPDATE users SET
            last_activity_date = ?,
            current_streak = ?,
            longest_streak = ?,
            is_online = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$today, $new_streak, $longest, $user_id]);

        // Streak badges
        if (
            $new_streak == 3 ||
            $new_streak == 7 ||
            $new_streak == 14 ||
            $new_streak == 30
        ) {
            create_notification(
                $user_id,
                "streak",
                "Streak Milestone!",
                "You've reached a {$new_streak}-day learning streak! 🔥",
            );
        }
    }
}

function add_xp($user_id, $xp, $source = "lesson")
{
    global $pdo;

    $stmt = $pdo->prepare(
        "UPDATE users SET xp = xp + ?, total_xp_earned = total_xp_earned + ? WHERE id = ?",
    );
    $stmt->execute([$xp, $xp, $user_id]);

    $user = get_user_by_id($user_id);
    $new_level = get_user_level($user["xp"]);

    $leveled_up = false;
    if ($new_level > $user["level"]) {
        $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);
        create_notification(
            $user_id,
            "level_up",
            "Level Up!",
            "You're now Level {$new_level}! 🚀",
        );
        $leveled_up = $new_level;
    }

    update_user_activity($user_id);
    $new_badges = check_and_award_badges($user_id);

    return [
        "xp_earned" => $xp,
        "total_xp" => $user["xp"] + $xp,
        "level_up" => $leveled_up,
        "new_badges" => $new_badges,
    ];
}

// ============== LANGUAGE FUNCTIONS ==============

function get_languages($active_only = true)
{
    global $pdo;
    $where = $active_only ? "WHERE is_active = TRUE" : "";
    $stmt = $pdo->prepare(
        "SELECT * FROM languages {$where} ORDER BY sort_order ASC",
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_language_by_id($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM languages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_language_by_slug($slug)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM languages WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// ============== LESSON FUNCTIONS ==============

function get_user_progress($user_id, $language_id = null)
{
    global $pdo;
    $sql = "
        SELECT l.*, up.completed, up.completed_at
        FROM lessons l
        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
    ";
    $params = [$user_id];

    if ($language_id) {
        $sql .= " WHERE l.language_id = ?";
        $params[] = $language_id;
    }

    $sql .= " ORDER BY l.language_id, l.order_num";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_lesson_by_id($lesson_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.*, lang.name as language_name, lang.slug as language_slug, lang.color as language_color, lang.icon as language_icon
        FROM lessons l
        JOIN languages lang ON l.language_id = lang.id
        WHERE l.id = ?
    ");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch();
}

function is_lesson_completed($user_id, $lesson_id)
{
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT completed FROM user_progress WHERE user_id = ? AND lesson_id = ?",
    );
    $stmt->execute([$user_id, $lesson_id]);
    $result = $stmt->fetch();
    return $result ? (bool) $result["completed"] : false;
}

function complete_lesson($user_id, $lesson_id, $code)
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT completed FROM user_progress WHERE user_id = ? AND lesson_id = ?",
    );
    $stmt->execute([$user_id, $lesson_id]);
    $progress = $stmt->fetch();

    if ($progress && $progress["completed"]) {
        return ["error" => "Already completed"];
    }

    $stmt = $pdo->prepare(
        "SELECT xp_reward, language_id FROM lessons WHERE id = ?",
    );
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();

    if (!$lesson) {
        return ["error" => "Lesson not found"];
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_progress (user_id, lesson_id, completed, code_submitted, completed_at)
        VALUES (?, ?, 1, ?, NOW())
        ON DUPLICATE KEY UPDATE
        completed = 1, code_submitted = ?, completed_at = NOW()
    ");
    $stmt->execute([$user_id, $lesson_id, $code, $code]);

    // Streak bonus
    $bonus = 0;
    $stmt = $pdo->prepare("SELECT current_streak FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user["current_streak"] >= 3) {
        $bonus = min($user["current_streak"] * 5, 100);
    }

    $xp_result = add_xp($user_id, $lesson["xp_reward"] + $bonus, "lesson");

    create_notification(
        $user_id,
        "lesson_completed",
        "Lesson Completed!",
        "You've completed a lesson and earned " .
            ($lesson["xp_reward"] + $bonus) .
            " XP!" .
            ($bonus > 0 ? " (Includes {$bonus} streak bonus!)" : ""),
    );

    return [
        "xp_earned" => $lesson["xp_reward"] + $bonus,
        "base_xp" => $lesson["xp_reward"],
        "streak_bonus" => $bonus,
        "level_up" => $xp_result["level_up"],
        "new_badges" => $xp_result["new_badges"],
    ];
}

function get_lessons_by_language($language_id, $difficulty = null)
{
    global $pdo;
    $sql = "SELECT l.*, lang.name as language_name, lang.color as language_color
            FROM lessons l JOIN languages lang ON l.language_id = lang.id
            WHERE l.language_id = ?";
    $params = [$language_id];

    if ($difficulty && $difficulty !== "all") {
        $sql .= " AND l.difficulty = ?";
        $params[] = $difficulty;
    }

    $sql .= " ORDER BY l.order_num ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============== LEADERBOARD ==============

function get_leaderboard($limit = 50)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*,
            COUNT(DISTINCT up.lesson_id) as lessons_completed
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id AND up.completed = 1
        GROUP BY u.id
        ORDER BY u.xp DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function get_language_leaderboard($language_id, $limit = 50)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*,
            COUNT(DISTINCT up.lesson_id) as lessons_completed
        FROM users u
        JOIN user_progress up ON u.id = up.user_id AND up.completed = 1
        JOIN lessons l ON up.lesson_id = l.id
        WHERE l.language_id = ?
        GROUP BY u.id
        ORDER BY u.xp DESC
        LIMIT ?
    ");
    $stmt->execute([$language_id, $limit]);
    return $stmt->fetchAll();
}

// ============== BATTLE ROYALE FUNCTIONS ==============

function create_br_match(
    $title,
    $type,
    $language_id,
    $difficulty,
    $challenge_data,
    $created_by,
) {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO br_matches
        (title, type, language_id, difficulty, challenge_description, starter_code, expected_output, max_players, time_limit_minutes, created_by, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'lobby')
    ");
    $stmt->execute([
        $title,
        $type,
        $language_id,
        $difficulty,
        $challenge_data["description"] ?? "",
        $challenge_data["starter_code"] ?? "",
        $challenge_data["expected_output"] ?? "",
        $challenge_data["max_players"] ?? BR_DEFAULT_MAX_PLAYERS,
        $challenge_data["time_limit"] ?? BR_DEFAULT_TIME_LIMIT,
        $created_by,
    ]);

    return $pdo->lastInsertId();
}

function join_br_match($match_id, $user_id)
{
    global $pdo;

    // Check if match exists and is in lobby
    $stmt = $pdo->prepare(
        "SELECT * FROM br_matches WHERE id = ? AND status = 'lobby'",
    );
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if (!$match) {
        return ["error" => "Match not found or already started"];
    }

    // Check if already joined
    $stmt = $pdo->prepare(
        "SELECT id FROM br_participants WHERE match_id = ? AND user_id = ?",
    );
    $stmt->execute([$match_id, $user_id]);
    if ($stmt->fetch()) {
        return ["error" => "Already joined"];
    }

    // Check player count
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM br_participants WHERE match_id = ?",
    );
    $stmt->execute([$match_id]);
    $count = $stmt->fetch()["count"];

    if ($count >= $match["max_players"]) {
        return ["error" => "Match is full"];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO br_participants (match_id, user_id) VALUES (?, ?)",
    );
    $stmt->execute([$match_id, $user_id]);

    create_notification(
        $user_id,
        "br_event",
        "Battle Royale Joined!",
        "You've joined '{$match["title"]}'. Get ready to code!",
    );

    // Auto-start if enough players
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM br_participants WHERE match_id = ?",
    );
    $stmt->execute([$match_id]);
    $count = $stmt->fetch()["count"];

    if ($count >= BR_MIN_PLAYERS_TO_START) {
        start_br_match($match_id);
    }

    return ["success" => true, "match" => $match];
}

function start_br_match($match_id)
{
    global $pdo;

    $stmt = $pdo->prepare(
        "UPDATE br_matches SET status = 'in_progress', started_at = NOW() WHERE id = ? AND status = 'lobby'",
    );
    $stmt->execute([$match_id]);

    // Notify all participants
    $stmt = $pdo->prepare(
        "SELECT user_id FROM br_participants WHERE match_id = ?",
    );
    $stmt->execute([$match_id]);
    $participants = $stmt->fetchAll();

    foreach ($participants as $p) {
        create_notification(
            $p["user_id"],
            "br_event",
            "Battle Royale Started!",
            "The match has begun! Write your code and survive!",
        );
    }
}

function submit_br_solution($match_id, $user_id, $code)
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT * FROM br_matches WHERE id = ? AND status = 'in_progress'",
    );
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if (!$match) {
        return ["error" => "Match not in progress"];
    }

    // Update participant
    $stmt = $pdo->prepare("
        UPDATE br_participants
        SET code_submitted = ?, score = score + 100, kills = kills + 1
        WHERE match_id = ? AND user_id = ?
    ");
    $stmt->execute([$code, $match_id, $user_id]);

    // Check if all submitted - then end match
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN code_submitted IS NOT NULL THEN 1 ELSE 0 END) as submitted
        FROM br_participants WHERE match_id = ?
    ");
    $stmt->execute([$match_id]);
    $stats = $stmt->fetch();

    if ($stats["total"] == $stats["submitted"]) {
        end_br_match($match_id);
    }

    return ["success" => true];
}

function end_br_match($match_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE br_matches SET status = 'completed', ended_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$match_id]);

    // Calculate positions
    $stmt = $pdo->prepare("
        SELECT id, user_id, score FROM br_participants
        WHERE match_id = ? ORDER BY score DESC
    ");
    $stmt->execute([$match_id]);
    $participants = $stmt->fetchAll();

    $position = 1;
    foreach ($participants as $p) {
        $is_winner = $position === 1;
        $stmt = $pdo->prepare("
            UPDATE br_participants
            SET finished_position = ?, is_winner = ?
            WHERE id = ?
        ");
        $stmt->execute([$position, $is_winner ? 1 : 0, $p["id"]]);

        // Award XP
        $xp = $is_winner ? XP_BATTLE_ROYALE_WIN : XP_BATTLE_ROYALE_PARTICIPATE;
        add_xp($p["user_id"], $xp, "battle_royale");

        if ($is_winner) {
            create_notification(
                $p["user_id"],
                "br_event",
                "🏆 BATTLE ROYALE WINNER!",
                "You've won the match! +{$xp} XP!",
            );
        }

        $position++;
    }
}

function get_active_br_matches($language_id = null)
{
    global $pdo;
    $sql = "SELECT m.*, lang.name as language_name, lang.color as language_color, lang.icon as language_icon,
                   (SELECT COUNT(*) FROM br_participants WHERE match_id = m.id) as player_count,
                   u.username as creator_name
            FROM br_matches m
            JOIN languages lang ON m.language_id = lang.id
            JOIN users u ON m.created_by = u.id
            WHERE m.status IN ('lobby', 'in_progress')";
    $params = [];

    if ($language_id) {
        $sql .= " AND m.language_id = ?";
        $params[] = $language_id;
    }

    $sql .= " ORDER BY m.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============== MINI-GAME FUNCTIONS ==============

function get_mini_games($type = null, $language_id = null)
{
    global $pdo;
    $sql = "SELECT mg.*, lang.name as language_name
            FROM mini_games mg
            JOIN languages lang ON mg.language_id = lang.id
            WHERE mg.is_active = TRUE";
    $params = [];

    if ($type) {
        $sql .= " AND mg.type = ?";
        $params[] = $type;
    }
    if ($language_id) {
        $sql .= " AND mg.language_id = ?";
        $params[] = $language_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function play_mini_game($game_id, $user_id, $score, $time_taken)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM mini_games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();

    if (!$game) {
        return ["error" => "Game not found"];
    }

    $stmt = $pdo->prepare("
        INSERT INTO mini_game_scores (user_id, game_id, score, time_taken, completed)
        VALUES (?, ?, ?, ?, TRUE)
    ");
    $stmt->execute([$user_id, $game_id, $score, $time_taken]);

    add_xp($user_id, $game["xp_reward"], "mini_game");

    return [
        "success" => true,
        "xp_earned" => $game["xp_reward"],
        "score" => $score,
        "time_taken" => $time_taken,
    ];
}

function get_mini_game_leaderboard($game_id, $limit = 10)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.username, mgs.score, mgs.time_taken, mgs.played_at
        FROM mini_game_scores mgs
        JOIN users u ON mgs.user_id = u.id
        WHERE mgs.game_id = ? AND mgs.completed = TRUE
        ORDER BY mgs.score DESC, mgs.time_taken ASC
        LIMIT ?
    ");
    $stmt->execute([$game_id, $limit]);
    return $stmt->fetchAll();
}

// ============== AI TUTOR FUNCTIONS ==============

function create_ai_chat($user_id, $language = "rust", $title = "New Chat")
{
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO ai_chats (user_id, title, language) VALUES (?, ?, ?)",
    );
    $stmt->execute([$user_id, $title, $language]);
    return $pdo->lastInsertId();
}

function get_ai_chats($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT ac.*,
            (SELECT content FROM ai_messages WHERE chat_id = ac.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM ai_chats ac
        WHERE ac.user_id = ?
        ORDER BY ac.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_ai_messages($chat_id, $user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM ai_messages
        WHERE chat_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$chat_id]);
    return $stmt->fetchAll();
}

function ask_ai_tutor($chat_id, $user_id, $message)
{
    global $pdo;

    // Verify chat belongs to user
    $stmt = $pdo->prepare(
        "SELECT * FROM ai_chats WHERE id = ? AND user_id = ?",
    );
    $stmt->execute([$chat_id, $user_id]);
    $chat = $stmt->fetch();

    if (!$chat) {
        return ["error" => "Chat not found"];
    }

    // Save user message
    $stmt = $pdo->prepare(
        "INSERT INTO ai_messages (chat_id, role, content) VALUES (?, 'user', ?)",
    );
    $stmt->execute([$chat_id, $message]);

    // Get conversation history
    $stmt = $pdo->prepare(
        "SELECT role, content FROM ai_messages WHERE chat_id = ? ORDER BY created_at ASC LIMIT 20",
    );
    $stmt->execute([$chat_id]);
    $history = $stmt->fetchAll();

    // Build context
    $context =
        "You are an expert programming tutor for the Rustnite coding platform. ";
    $context .= "You help users learn to code in various languages. ";
    $context .= "The current language is: {$chat["language"]}. ";
    $context .=
        "Provide clear, concise explanations with code examples when helpful. ";
    $context .= "Keep responses educational and encouraging.\n\n";

    foreach ($history as $msg) {
        $context .=
            ($msg["role"] === "user" ? "User: " : "Assistant: ") .
            $msg["content"] .
            "\n";
    }

    $context .= "Assistant: ";

    // Big Pickle AI response (built-in tutor)
    $response = generate_ai_response($context, $chat["language"]);

    // Save assistant message
    $stmt = $pdo->prepare(
        "INSERT INTO ai_messages (chat_id, role, content) VALUES (?, 'assistant', ?)",
    );
    $stmt->execute([$chat_id, $response]);

    // Update chat title if first message
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM ai_messages WHERE chat_id = ?",
    );
    $stmt->execute([$chat_id]);
    $count = $stmt->fetch()["count"];

    if ($count <= 2) {
        $title = substr($message, 0, 50) . (strlen($message) > 50 ? "..." : "");
        $stmt = $pdo->prepare("UPDATE ai_chats SET title = ? WHERE id = ?");
        $stmt->execute([$title, $chat_id]);
    }

    // Award small XP for asking questions
    add_xp($user_id, XP_AI_TUTOR_QUESTION, "ai_tutor");

    // Extract code blocks for syntax highlighting
    preg_match_all('/```(\w+)?\n(.*?)```/s', $response, $code_blocks);
    $blocks = [];
    for ($i = 0; $i < count($code_blocks[0]); $i++) {
        $blocks[] = [
            "language" => $code_blocks[1][$i] ?? $chat["language"],
            "code" => $code_blocks[2][$i],
        ];
    }

    return [
        "success" => true,
        "response" => $response,
        "code_blocks" => $blocks,
        "xp_earned" => XP_AI_TUTOR_QUESTION,
    ];
}

function generate_ai_response($context, $language)
{
    // Try the OpenCode API first if configured
    if (defined("OPENCODE_API_KEY") && !empty(OPENCODE_API_KEY)) {
        $response = call_opencode_api($context, $language);
        if ($response !== null) {
            return $response;
        }
    }

    // Fallback: Big Pickle AI built-in intelligence
    // Only used when no API key is configured or API call fails
    return generate_fallback_response($context, $language);
}

function call_opencode_api($context, $language)
{
    $messages = [
        [
            "role" => "system",
            "content" =>
                "You are Big Pickle, an expert programming tutor for the Rustnite coding platform. " .
                "You help users learn to code in various languages including Rust, Python, JavaScript, TypeScript, Go, Java, C++, and C. " .
                "Provide clear, concise explanations with working code examples. " .
                "Be encouraging and educational. Keep responses focused and practical. " .
                "Current language context: {$language}.",
        ],
        [
            "role" => "user",
            "content" => $context,
        ],
    ];

    $payload = json_encode([
        "model" => AI_TUTOR_MODEL,
        "messages" => $messages,
        "max_tokens" => AI_TUTOR_MAX_TOKENS,
        "temperature" => AI_TUTOR_TEMPERATURE,
    ]);

    $ch = curl_init(OPENCODE_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENCODE_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response && $http_code === 200) {
        $result = json_decode($response, true);
        // OpenAI-compatible format
        if (isset($result["choices"][0]["message"]["content"])) {
            return $result["choices"][0]["message"]["content"];
        }
        // OpenCode API format
        if (isset($result["data"]) && !empty($result["data"])) {
            return $result["data"];
        }
        // Simple response format
        if (isset($result["response"])) {
            return $result["response"];
        }
        // Content field directly
        if (isset($result["content"])) {
            return $result["content"];
        }
    }

    return null; // Fallback to built-in
}

function generate_fallback_response($context, $language)
{
    $lower_context = strtolower($context);
    $trimmed = trim(
        str_replace(["User: ", "Assistant: ", "\n"], " ", $lower_context),
    );

    // Detect question type
    $is_greeting =
        preg_match("/\b(hi|hello|hey|sup|howdy|yo|\g{d})\b/", $trimmed) &&
        strlen($trimmed) < 20;
    $is_explanation = preg_match(
        "/\b(what is|explain|how does|tell me about|define|describe|what are|what's|how do)\b/",
        $trimmed,
    );
    $is_help_with_code = preg_match(
        "/\b(help|fix|debug|error|bug|wrong|issue|not working|broken|crash)\b/",
        $trimmed,
    );
    $is_example = preg_match(
        "/\b(example|show me|demonstrate|sample|snippet)\b/",
        $trimmed,
    );
    $is_question = preg_match(
        "/\?\s*$|\b(how|why|when|where|which|can you|could you|would you)\b/",
        $trimmed,
    );
    $is_concept = preg_match(
        "/\b(variable|function|class|loop|array|string|int|bool|null|object|method|property|inheritance|polymorphism|recursion|algorithm)\b/",
        $trimmed,
    );
    $is_mini_game_create = preg_match(
        "/\b(create|generate|make|new)\b.*\b(mini.game|game|challenge)\b/",
        $trimmed,
    );
    $is_language_question = preg_match(
        "/\b(rust|python|javascript|typescript|go|java|c\+\+|c\s*language)\b/",
        $trimmed,
    );

    preg_match('/```(\w+)?\n(.*?)```/s', $context, $user_code);
    $has_code = !empty($user_code);

    // Extract the actual user question (remove system prompt)
    $parts = explode("User: ", $context);
    $user_question = end($parts);
    $user_question = preg_replace("/\nAssistant:.*$/s", "", $user_question);
    $user_question = trim($user_question);

    $response = "";

    if ($is_greeting) {
        $greetings = [
            "Hey there! \ud83d\udc4b Ready to write some {$language}? What can I help you with today?",
            "Hello! \ud83d\ude80 I'm Big Pickle, your coding tutor. Ask me anything about {$language}!",
            "Hi! \ud83c\udf89 What {$language} question can I help you tackle today?",
        ];
        $response = $greetings[array_rand($greetings)];
    } elseif ($is_help_with_code && $has_code) {
        $code = $user_code[2] ?? "";
        $lines = explode("\n", $code);
        $line_count = count($lines);
        $response = "I see you've shared some code (\u2248{$line_count} lines). Let me help you debug it:\n\n";
        $response .= "**What I'm looking at:**\n```{$language}\n{$code}\n```\n\n";
        $response .= "**Common things to check in {$language}:**\n";
        $lang_checks = [
            "rust" =>
                "- Ownership/borrowing rules\n- Semicolons and curly braces\n- Type annotations\n- Pattern matching exhaustiveness",
            "python" =>
                "- Indentation (4 spaces)\n- Colon after if/for/def\n- Import statements\n- Variable scope",
            "javascript" =>
                "- Semicolons and braces\n- Async/await handling\n- 'this' context\n- Array/object destructuring",
            "typescript" =>
                "- Type annotations\n- Interface vs type\n- Strict null checks\n- Generic constraints",
            "go" =>
                "- Package declarations\n- Error handling\n- Goroutine synchronization\n- Interface satisfaction",
            "java" =>
                "- Class/method declarations\n- Exception handling\n- Generic type parameters\n- Access modifiers",
            "cpp" =>
                "- Memory management\n- Header includes\n- Template syntax\n- Destructor/virtual rules",
            "c" =>
                "- Pointer arithmetic\n- Memory allocation\n- Header includes\n- Buffer sizes",
        ];
        $response .= $lang_checks[$language] ?? $lang_checks["rust"];
        $response .=
            "\n\nCould you tell me what error you're seeing or what behavior is unexpected? That'll help me give a more specific fix!";
    } elseif ($is_mini_game_create) {
        $response = "\ud83c\udfae **Mini-Game Creator**\n\nI'll help you make a coding mini-game! Head to the **Mini-Games Arena** and click **AI Generate** to create a custom game. You can pick language, type (Syntax Speed, Bug Hunt, Output Prediction, Code Race), and difficulty. The AI will build it with fresh challenges specific to {$language}!";
    } elseif ($is_explanation || $is_concept || $is_question || $is_example) {
        // Extract key concept from question
        $concepts = [
            "variable" => [
                "Variables store data in memory. In {$language}, you declare them with specific syntax. They can be mutable (changeable) or immutable (fixed).",
                "```{$language}\n// Example:\nlet x = 5; // immutable\nlet mut y = 10; // mutable\n```",
            ],
            "function" => [
                "Functions are reusable blocks of code. They take inputs (parameters), perform operations, and return outputs.",
                "```{$language}\n// Function pattern:\nfn add(a: i32, b: i32) -> i32 {\n    a + b\n}\n```",
            ],
            "class" => [
                "Classes are blueprints for creating objects. They encapsulate data and behavior together.",
                "```{$language}\n// Class pattern varies by language\n```",
            ],
            "loop" => [
                "Loops let you repeat code multiple times. Common types: for, while, and loop.",
                "```{$language}\n// Loop example\nfor i in 0..5 {\n    println!(\"{} \", i);\n}\n```",
            ],
            "array" => [
                "Arrays store multiple values of the same type in sequence. They're zero-indexed and fixed-size.",
                "```{$language}\n// Array example\nlet arr = [1, 2, 3, 4, 5];\n```",
            ],
            "string" => [
                "Strings hold text data. Different languages handle strings differently - some as objects, others as character arrays.",
                "```{$language}\n// String handling varies by language\n```",
            ],
        ];

        $found = false;
        foreach ($concepts as $keyword => $info) {
            if (strpos($trimmed, $keyword) !== false) {
                $response = "**{$keyword}**\n\n{$info[0]}\n\n{$info[1]}\n\nWant me to go deeper into any specific aspect?";
                $found = true;
                break;
            }
        }

        if (!$found) {
            $response = "Great question about {$language}! \n\nHere's what I can tell you:\n\n";
            $response .= get_language_fact($language) . "\n\n";
            $response .=
                "Could you be more specific about what you'd like to learn? I can help with:\n";
            $response .=
                "- **Syntax** \u2014 How to write specific constructs\n";
            $response .=
                "- **Concepts** \u2014 Variables, functions, types, etc.\n";
            $response .= "- **Debugging** \u2014 Fix errors in your code\n";
            $response .= "- **Best practices** \u2014 Idiomatic patterns\n\n";
            $response .= "Just paste your code or ask away! \ud83d\ude80";
        }
    } else {
        $response = "Hey! I'm Big Pickle \ud83e\udd0d\n\n";
        $response .= "I can help you with:\n";
        $response .=
            "- \ud83d\udcdd **Explaining concepts** \u2014 \"What is a closure in Rust?\"\n";
        $response .=
            "- \ud83d\udee1\ufe0f **Debugging code** \u2014 Paste your code and I'll help fix it\n";
        $response .=
            "- \ud83d\udca1 **Code examples** \u2014 \"Show me a linked list in Go\"\n";
        $response .=
            "- \ud83c\udfae **Mini-games** \u2014 Ask me to create a coding game\n\n";
        $response .= "Current language: **{$language}**. What would you like to learn?";
    }

    // Always award XP for engaging
    return $response;
}

// ============== AI MINI-GAME GENERATION ==============

function generate_mini_game(
    $language_slug,
    $type = "syntax_speed",
    $difficulty = "beginner",
) {
    global $pdo;

    // Get language info
    $lang = get_language_by_slug($language_slug);
    if (!$lang) {
        return ["error" => "Language not found"];
    }

    // Build game content based on type using Big Pickle intelligence
    switch ($type) {
        case "syntax_speed":
            return generate_syntax_speed_game($lang, $difficulty);
        case "bug_hunt":
            return generate_bug_hunt_game($lang, $difficulty);
        case "output_prediction":
            return generate_output_prediction_game($lang, $difficulty);
        case "code_race":
            return generate_code_race_game($lang, $difficulty);
        default:
            return generate_syntax_speed_game($lang, $difficulty);
    }
}

function generate_syntax_speed_game($lang, $difficulty)
{
    $language = $lang["slug"];
    $questions = [];

    // Common syntax patterns per language
    $patterns = get_syntax_patterns($language, $difficulty);
    shuffle($patterns);

    $count = min(8, count($patterns));
    for ($i = 0; $i < $count; $i++) {
        $questions[] = $patterns[$i];
    }

    $time_limit =
        $difficulty === "beginner"
            ? 45
            : ($difficulty === "intermediate"
                ? 30
                : 20);
    $xp =
        $difficulty === "beginner"
            ? 100
            : ($difficulty === "intermediate"
                ? 200
                : 300);

    return [
        "title" => ucfirst($language) . " Syntax Sprint",
        "description" => "Type the correct {$language} syntax as fast as you can! ({$difficulty})",
        "type" => "syntax_speed",
        "difficulty" => $difficulty,
        "language_id" => $lang["id"],
        "game_data" => json_encode([
            "time_limit" => $time_limit,
            "questions" => $questions,
        ]),
        "xp_reward" => $xp,
    ];
}

function generate_bug_hunt_game($lang, $difficulty)
{
    $language = $lang["slug"];
    $bugs = [];

    $bug_patterns = get_bug_patterns($language, $difficulty);
    shuffle($bug_patterns);

    $count = min(5, count($bug_patterns));
    for ($i = 0; $i < $count; $i++) {
        $bugs[] = $bug_patterns[$i];
    }

    $time_limit =
        $difficulty === "beginner"
            ? 180
            : ($difficulty === "intermediate"
                ? 120
                : 60);
    $xp =
        $difficulty === "beginner"
            ? 150
            : ($difficulty === "intermediate"
                ? 250
                : 400);

    return [
        "title" => ucfirst($language) . " Bug Hunt",
        "description" => "Find and fix bugs in {$language} code! ({$difficulty})",
        "type" => "bug_hunt",
        "difficulty" => $difficulty,
        "language_id" => $lang["id"],
        "game_data" => json_encode([
            "time_limit" => $time_limit,
            "bugs" => $bugs,
        ]),
        "xp_reward" => $xp,
    ];
}

function generate_output_prediction_game($lang, $difficulty)
{
    $language = $lang["slug"];
    $questions = [];

    $prediction_patterns = get_prediction_patterns($language, $difficulty);
    shuffle($prediction_patterns);

    $count = min(6, count($prediction_patterns));
    for ($i = 0; $i < $count; $i++) {
        $questions[] = $prediction_patterns[$i];
    }

    $xp =
        $difficulty === "beginner"
            ? 120
            : ($difficulty === "intermediate"
                ? 200
                : 350);

    return [
        "title" => ucfirst($language) . " Output Oracle",
        "description" => "Predict what this {$language} code will output! ({$difficulty})",
        "type" => "output_prediction",
        "difficulty" => $difficulty,
        "language_id" => $lang["id"],
        "game_data" => json_encode([
            "questions" => $questions,
        ]),
        "xp_reward" => $xp,
    ];
}

function generate_code_race_game($lang, $difficulty)
{
    $language = $lang["slug"];
    $rounds = [];

    $race_patterns = get_syntax_patterns($language, $difficulty);
    shuffle($race_patterns);

    $count = min(5, count($race_patterns));
    for ($i = 0; $i < $count; $i++) {
        $rounds[] = [
            "code" => $race_patterns[$i]["answer"],
            "hint" => $race_patterns[$i]["hint"] ?? "",
        ];
    }

    $time_limit =
        $difficulty === "beginner"
            ? 90
            : ($difficulty === "intermediate"
                ? 60
                : 40);
    $xp =
        $difficulty === "beginner"
            ? 150
            : ($difficulty === "intermediate"
                ? 250
                : 400);

    return [
        "title" => ucfirst($language) . " Code Race",
        "description" => "Race to type {$language} code correctly! ({$difficulty})",
        "type" => "code_race",
        "difficulty" => $difficulty,
        "language_id" => $lang["id"],
        "game_data" => json_encode([
            "time_limit" => $time_limit,
            "rounds" => $rounds,
        ]),
        "xp_reward" => $xp,
    ];
}

function get_syntax_patterns($language, $difficulty)
{
    $patterns = [
        "rust" => [
            "beginner" => [
                [
                    "prompt" => "Print to console in Rust",
                    "answer" => 'println!("Hello");',
                    "hint" => "Use println! macro",
                ],
                [
                    "prompt" => "Declare an immutable variable in Rust",
                    "answer" => "let x = 5;",
                    "hint" => "Use let keyword",
                ],
                [
                    "prompt" => "Declare a mutable variable in Rust",
                    "answer" => "let mut x = 5;",
                    "hint" => "Add mut after let",
                ],
                [
                    "prompt" => "Define a function in Rust",
                    "answer" => "fn main() {}",
                    "hint" => "Use fn keyword",
                ],
                [
                    "prompt" => "Create a Vec in Rust",
                    "answer" => "let v = vec![1, 2, 3];",
                    "hint" => "Use vec! macro",
                ],
                [
                    "prompt" => "If statement in Rust",
                    "answer" => "if x > 0 {}",
                    "hint" => "No parentheses needed",
                ],
                [
                    "prompt" => "For loop in Rust",
                    "answer" => "for i in 0..10 {}",
                    "hint" => "Use for..in range",
                ],
                [
                    "prompt" => "While loop in Rust",
                    "answer" => "while true {}",
                    "hint" => "Use while keyword",
                ],
                [
                    "prompt" => "Return a value from function",
                    "answer" => "fn add(a: i32, b: i32) -> i32 { a + b }",
                    "hint" => "Last expression is return",
                ],
                [
                    "prompt" => "Match statement in Rust",
                    "answer" => "match x { 1 => true, _ => false }",
                    "hint" => "Use match keyword",
                ],
            ],
            "intermediate" => [
                [
                    "prompt" => "Define a struct in Rust",
                    "answer" => "struct Point { x: i32, y: i32 }",
                    "hint" => "Use struct keyword",
                ],
                [
                    "prompt" => "Implement a trait in Rust",
                    "answer" => "impl Display for Point {}",
                    "hint" => "Use impl Trait for Type",
                ],
                [
                    "prompt" => "Use match with enum",
                    "answer" =>
                        "match self { Option::Some(v) => v, None => 0 }",
                    "hint" => "Match all variants",
                ],
                [
                    "prompt" => "Read file in Rust",
                    "answer" =>
                        'let contents = fs::read_to_string("file.txt").unwrap();',
                    "hint" => "Use fs module",
                ],
                [
                    "prompt" => "Handle Result type",
                    "answer" => 'let val = result.expect("error message");',
                    "hint" => "Use expect or unwrap",
                ],
            ],
            "advanced" => [
                [
                    "prompt" => "Define a generic function",
                    "answer" => "fn identity<T>(x: T) -> T { x }",
                    "hint" => "Use <T> syntax",
                ],
                [
                    "prompt" => "Use Box for heap allocation",
                    "answer" => "let b = Box::new(5);",
                    "hint" => "Box::new()",
                ],
                [
                    "prompt" => "Create a closure",
                    "answer" => "let add = |a, b| a + b;",
                    "hint" => "Use || syntax",
                ],
                [
                    "prompt" => "Use Arc for thread safety",
                    "answer" => "let arc = Arc::new(value);",
                    "hint" => "Arc::new()",
                ],
                [
                    "prompt" => "Define a macro in Rust",
                    "answer" => "macro_rules! my_macro { () => {} }",
                    "hint" => "Use macro_rules!",
                ],
            ],
        ],
        "python" => [
            "beginner" => [
                [
                    "prompt" => "Print to console in Python",
                    "answer" => 'print("Hello")',
                    "hint" => "Use print() function",
                ],
                [
                    "prompt" => "Declare a variable in Python",
                    "answer" => "x = 5",
                    "hint" => "No keyword needed",
                ],
                [
                    "prompt" => "If statement in Python",
                    "answer" => "if x > 0:",
                    "hint" => "End with colon",
                ],
                [
                    "prompt" => "For loop in Python",
                    "answer" => "for i in range(10):",
                    "hint" => "Use for..in range",
                ],
                [
                    "prompt" => "Define a function in Python",
                    "answer" => "def hello():",
                    "hint" => "Use def keyword",
                ],
                [
                    "prompt" => "Create a list in Python",
                    "answer" => "my_list = [1, 2, 3]",
                    "hint" => "Use square brackets",
                ],
                [
                    "prompt" => "While loop in Python",
                    "answer" => "while True:",
                    "hint" => "End with colon",
                ],
                [
                    "prompt" => "Return a value in Python",
                    "answer" => "return value",
                    "hint" => "Use return keyword",
                ],
            ],
            "intermediate" => [
                [
                    "prompt" => "Create a class in Python",
                    "answer" => "class MyClass:",
                    "hint" => "Use class keyword",
                ],
                [
                    "prompt" => "Try-except in Python",
                    "answer" => "try: except Exception:",
                    "hint" => "Use try/except",
                ],
                [
                    "prompt" => "List comprehension in Python",
                    "answer" => "[x*2 for x in range(10)]",
                    "hint" => "Use [expr for var in iter]",
                ],
                [
                    "prompt" => "Open a file in Python",
                    "answer" => 'with open("file.txt") as f:',
                    "hint" => "Use with statement",
                ],
                [
                    "prompt" => "Import a module in Python",
                    "answer" => "import math",
                    "hint" => "Use import keyword",
                ],
            ],
            "advanced" => [
                [
                    "prompt" => "Create a decorator in Python",
                    "answer" => "def decorator(func): def wrapper(): pass",
                    "hint" => "Nested function",
                ],
                [
                    "prompt" => "Generator expression in Python",
                    "answer" => "(x*2 for x in range(10))",
                    "hint" => "Use () instead of []",
                ],
                [
                    "prompt" => "Lambda function in Python",
                    "answer" => "lambda x: x * 2",
                    "hint" => "Use lambda keyword",
                ],
                [
                    "prompt" => "Async function in Python",
                    "answer" => "async def fetch_data():",
                    "hint" => "Use async def",
                ],
                [
                    "prompt" => "Type hint in Python",
                    "answer" => "def add(x: int, y: int) -> int:",
                    "hint" => "Add : type after parameter",
                ],
            ],
        ],
        "javascript" => [
            "beginner" => [
                [
                    "prompt" => "Print to console in JS",
                    "answer" => 'console.log("Hello");',
                    "hint" => "Use console.log()",
                ],
                [
                    "prompt" => "Declare a variable in JS",
                    "answer" => "let x = 5;",
                    "hint" => "Use let or const",
                ],
                [
                    "prompt" => "If statement in JS",
                    "answer" => "if (x > 0) {}",
                    "hint" => "Parentheses required",
                ],
                [
                    "prompt" => "For loop in JS",
                    "answer" => "for (let i = 0; i < 10; i++) {}",
                    "hint" => "C-style for loop",
                ],
                [
                    "prompt" => "Define a function in JS",
                    "answer" => "function hello() {}",
                    "hint" => "Use function keyword",
                ],
                [
                    "prompt" => "Create an array in JS",
                    "answer" => "const arr = [1, 2, 3];",
                    "hint" => "Use square brackets",
                ],
                [
                    "prompt" => "Arrow function in JS",
                    "answer" => "const add = (a, b) => a + b;",
                    "hint" => "Use => syntax",
                ],
                [
                    "prompt" => "While loop in JS",
                    "answer" => "while (true) {}",
                    "hint" => "Use while keyword",
                ],
            ],
            "intermediate" => [
                [
                    "prompt" => "Create an object in JS",
                    "answer" => 'const obj = { key: "value" };',
                    "hint" => "Use curly braces",
                ],
                [
                    "prompt" => "Promise in JS",
                    "answer" => "new Promise((resolve, reject) => {})",
                    "hint" => "Pass executor function",
                ],
                [
                    "prompt" => "Array map in JS",
                    "answer" => "arr.map(x => x * 2)",
                    "hint" => "Use .map()",
                ],
                [
                    "prompt" => "Template literal in JS",
                    "answer" => '`Hello ${name}`',
                    "hint" => 'Use backticks and ${}',
                ],
                [
                    "prompt" => "Destructuring in JS",
                    "answer" => "const { name, age } = obj;",
                    "hint" => "Use {} destructuring",
                ],
            ],
            "advanced" => [
                [
                    "prompt" => "Async/await in JS",
                    "answer" => "async function fetchData() { await response }",
                    "hint" => "Use async/await",
                ],
                [
                    "prompt" => "Class in JS",
                    "answer" => "class MyClass extends BaseClass {}",
                    "hint" => "Use class/extends",
                ],
                [
                    "prompt" => "Spread operator in JS",
                    "answer" => "const merged = { ...obj1, ...obj2 };",
                    "hint" => "Use ... spread",
                ],
                [
                    "prompt" => "Reduce array in JS",
                    "answer" => "arr.reduce((acc, val) => acc + val, 0)",
                    "hint" => "Use .reduce()",
                ],
            ],
        ],
    ];

    // Default to rust if language not found
    $lang_patterns = $patterns[$language] ?? $patterns["rust"];
    return $lang_patterns[$difficulty] ?? $lang_patterns["beginner"];
}

function get_bug_patterns($language, $difficulty)
{
    $bugs = [
        "rust" => [
            "beginner" => [
                [
                    "code" => 'prntln!("Hello");',
                    "fix" => 'println!("Hello");',
                    "hint" => "Missing l in println",
                ],
                [
                    "code" => 'let x = 5\nx = 6',
                    "fix" => 'let mut x = 5;\nx = 6;',
                    "hint" => "Variable must be mutable",
                ],
                [
                    "code" => 'fn main() {\n    return 0;\n}',
                    "fix" => 'fn main() {\n    // return 0;\n}',
                    "hint" => 'main() doesn\'t return a value',
                ],
                [
                    "code" => 'if x > 0 {\n    true\n}',
                    "fix" => 'if x > 0 {\n    // true\n}',
                    "hint" => "Missing parentheses (add them or remove)",
                ],
                [
                    "code" => 'let name: str = "hello";',
                    "fix" => 'let name: &str = "hello";',
                    "hint" => "String literals are &str",
                ],
            ],
            "intermediate" => [
                [
                    "code" => 'fn get_value() -> i32 {\n    return "hello";\n}',
                    "fix" => 'fn get_value() -> &str {\n    return "hello";\n}',
                    "hint" => "Return type mismatch",
                ],
                [
                    "code" => 'let v = vec![1, 2, 3];\nlet x = v[10];',
                    "fix" =>
                        'let v = vec![1, 2, 3];\nlet x = v.get(10).unwrap_or(&0);',
                    "hint" => "Index out of bounds - use .get()",
                ],
                [
                    "code" => 'let s = String::from("hello");\nlet c = s[0];',
                    "fix" =>
                        'let s = String::from("hello");\nlet c = s.chars().next().unwrap();',
                    "hint" => "Cannot index String directly",
                ],
            ],
            "advanced" => [
                [
                    "code" =>
                        'fn main() {\n    let r;\n    { let x = 5; r = &x; }\n    println!("{}", r);\n}',
                    "fix" =>
                        'fn main() {\n    let x = 5;\n    let r = &x;\n    println!("{}", r);\n}',
                    "hint" => "Dangling reference - x dropped too early",
                ],
                [
                    "code" =>
                        'use std::rc::Rc;\nlet x = Rc::new(5);\n*std::thread::spawn(move || { *x })',
                    "fix" =>
                        'use std::sync::Arc;\nlet x = Arc::new(5);\nstd::thread::spawn(move || { *x })',
                    "hint" => "Rc is not thread-safe, use Arc",
                ],
            ],
        ],
        "python" => [
            "beginner" => [
                [
                    "code" => 'prnt("Hello")',
                    "fix" => 'print("Hello")',
                    "hint" => "Missing i in print",
                ],
                [
                    "code" => "if x = 5:",
                    "fix" => "if x == 5:",
                    "hint" => "Use == for comparison, not =",
                ],
                [
                    "code" => 'def hello():\nprint("hi")',
                    "fix" => 'def hello():\n    print("hi")',
                    "hint" => "Indent the body with 4 spaces",
                ],
                [
                    "code" => "for i in 10:",
                    "fix" => "for i in range(10):",
                    "hint" => "Use range() to iterate numbers",
                ],
            ],
            "intermediate" => [
                [
                    "code" => 'x = 10\nif x > 5 and < 20:',
                    "fix" => 'x = 10\nif x > 5 and x < 20:',
                    "hint" => "Must repeat variable after and",
                ],
                [
                    "code" =>
                        'def add(a, b):\n    return a + b\n\nadd(5, "10")',
                    "fix" => 'def add(a, b):\n    return a + b\n\nadd(5, 10)',
                    "hint" => "Cannot add int and str",
                ],
            ],
        ],
        "javascript" => [
            "beginner" => [
                [
                    "code" => 'console.log("Hello";',
                    "fix" => 'console.log("Hello");',
                    "hint" => "Missing closing parenthesis",
                ],
                [
                    "code" => "if x > 0 { }",
                    "fix" => "if (x > 0) { }",
                    "hint" => "Parentheses required around condition",
                ],
                [
                    "code" => 'const x = 5\nx = 6',
                    "fix" => 'let x = 5;\nx = 6;',
                    "hint" => "Cannot reassign const, use let",
                ],
                [
                    "code" =>
                        'function hello() {\n    return;\nconsole.log("never runs");\n}',
                    "fix" =>
                        'function hello() {\n    console.log("runs");\n    return;\n}',
                    "hint" => "Code after return unreachable",
                ],
            ],
            "intermediate" => [
                [
                    "code" => "[1, 2, 3].map(x => { return x * 2 })",
                    "fix" => "[1, 2, 3].map(x => x * 2)",
                    "hint" => 'Single expression arrow doesn\'t need braces',
                ],
                [
                    "code" => 'const obj = {\n    name: "test"\n    age: 25\n}',
                    "fix" => 'const obj = {\n    name: "test",\n    age: 25\n}',
                    "hint" => "Missing comma between properties",
                ],
            ],
        ],
    ];

    $lang_bugs = $bugs[$language] ?? $bugs["rust"];
    return $lang_bugs[$difficulty] ?? $lang_bugs["beginner"];
}

function get_prediction_patterns($language, $difficulty)
{
    $predictions = [
        "rust" => [
            "beginner" => [
                [
                    "code" => 'println!("{}", 2 + 3);',
                    "options" => ["5", "23", "Error", "None"],
                    "correct" => 0,
                ],
                [
                    "code" => 'let x = 5;\nlet y = x + 2;\nprintln!("{}", y);',
                    "options" => ["5", "7", "2", "Error"],
                    "correct" => 1,
                ],
                [
                    "code" => 'let mut x = 10;\nx += 5;\nprintln!("{}", x);',
                    "options" => ["10", "15", "5", "Error"],
                    "correct" => 1,
                ],
                [
                    "code" =>
                        'let v = vec![1, 2, 3];\nprintln!("{}", v.len());',
                    "options" => ["1", "2", "3", "4"],
                    "correct" => 2,
                ],
                [
                    "code" =>
                        'let s = String::from("hello");\nprintln!("{}", s.len());',
                    "options" => ["3", "4", "5", "6"],
                    "correct" => 2,
                ],
            ],
            "intermediate" => [
                [
                    "code" =>
                        'fn add(x: i32, y: i32) -> i32 { x + y }\nprintln!("{}", add(3, 4));',
                    "options" => ["3", "4", "7", "Error"],
                    "correct" => 2,
                ],
                [
                    "code" =>
                        'let x = Some(5);\nif let Some(v) = x {\n    println!("{}", v);\n}',
                    "options" => ["None", "5", "Some(5)", "Error"],
                    "correct" => 1,
                ],
                [
                    "code" =>
                        'let nums = vec![1, 2, 3, 4, 5];\nlet sum: i32 = nums.iter().sum();\nprintln!("{}", sum);',
                    "options" => ["10", "12", "14", "15"],
                    "correct" => 3,
                ],
            ],
            "advanced" => [
                [
                    "code" =>
                        'let nums = vec![1, 2, 3];\nlet doubled: Vec<i32> = nums.iter().map(|x| x * 2).collect();\nprintln!("{:?}", doubled);',
                    "options" => [
                        "[1, 2, 3]",
                        "[2, 4, 6]",
                        "[0, 1, 2]",
                        "Error",
                    ],
                    "correct" => 1,
                ],
                [
                    "code" =>
                        'let x: Result<i32, &str> = Ok(42);\nlet y = x.unwrap_or(0);\nprintln!("{}", y);',
                    "options" => ["0", "42", "Error", "Ok(42)"],
                    "correct" => 1,
                ],
            ],
        ],
        "python" => [
            "beginner" => [
                [
                    "code" => "print(2 + 3)",
                    "options" => ["5", "23", "Error", "None"],
                    "correct" => 0,
                ],
                [
                    "code" => 'x = 10\nprint(x * 2)',
                    "options" => ["10", "12", "20", "Error"],
                    "correct" => 2,
                ],
                [
                    "code" => "print(type(42))",
                    "options" => ['<class \'int\'>', "int", "number", "Error"],
                    "correct" => 0,
                ],
            ],
            "intermediate" => [
                [
                    "code" => 'def add(a, b): return a + b\nprint(add(3, 4))',
                    "options" => ["3", "4", "7", "Error"],
                    "correct" => 2,
                ],
                [
                    "code" => 'nums = [1, 2, 3]\nprint(len(nums))',
                    "options" => ["1", "2", "3", "4"],
                    "correct" => 2,
                ],
            ],
        ],
        "javascript" => [
            "beginner" => [
                [
                    "code" => "console.log(typeof 42)",
                    "options" => ["number", "string", "object", "undefined"],
                    "correct" => 0,
                ],
                [
                    "code" => 'let x = 10;\nconsole.log(x + 5);',
                    "options" => ["10", "15", "5", "Error"],
                    "correct" => 1,
                ],
                [
                    "code" => "console.log([1, 2, 3].length);",
                    "options" => ["1", "2", "3", "4"],
                    "correct" => 2,
                ],
            ],
            "intermediate" => [
                [
                    "code" =>
                        'const add = (a, b) => a + b;\nconsole.log(add(3, 4));',
                    "options" => ["3", "4", "7", "Error"],
                    "correct" => 2,
                ],
                [
                    "code" =>
                        'const arr = [1, 2, 3];\nconsole.log(arr.map(x => x * 2));',
                    "options" => [
                        "[1, 2, 3]",
                        "[2, 4, 6]",
                        "[0, 2, 4]",
                        "Error",
                    ],
                    "correct" => 1,
                ],
            ],
        ],
    ];

    $lang_preds = $predictions[$language] ?? $predictions["rust"];
    return $lang_preds[$difficulty] ?? $lang_preds["beginner"];
}

function create_ai_generated_mini_game(
    $user_id,
    $language_slug,
    $type = "syntax_speed",
    $difficulty = "beginner",
) {
    global $pdo;

    $result = generate_mini_game($language_slug, $type, $difficulty);

    if (isset($result["error"])) {
        return $result;
    }

    // Check if a similar AI-generated game already exists
    $stmt = $pdo->prepare(
        "SELECT id FROM mini_games WHERE title = ? AND language_id = ? AND type = ? AND difficulty = ?",
    );
    $stmt->execute([
        $result["title"],
        $result["language_id"],
        $result["type"],
        $result["difficulty"],
    ]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing game data
        $stmt = $pdo->prepare(
            "UPDATE mini_games SET game_data = ? WHERE id = ?",
        );
        $stmt->execute([$result["game_data"], $existing["id"]]);
        return [
            "action" => "updated",
            "game_id" => $existing["id"],
            "title" => $result["title"],
        ];
    }

    // Insert new game
    $stmt = $pdo->prepare(
        "INSERT INTO mini_games (title, description, type, difficulty, language_id, game_data, xp_reward, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)",
    );
    $stmt->execute([
        $result["title"],
        $result["description"],
        $result["type"],
        $result["difficulty"],
        $result["language_id"],
        $result["game_data"],
        $result["xp_reward"],
    ]);

    $game_id = $pdo->lastInsertId();

    create_notification(
        $user_id,
        "mini_game",
        "New AI-Generated Game!",
        "A new {$difficulty} {$result["type"]} game for {$language_slug} has been created: {$result["title"]}!",
    );

    return [
        "action" => "created",
        "game_id" => $game_id,
        "title" => $result["title"],
    ];
}

function get_language_fact($language)
{
    $facts = [
        "rust" =>
            "🦀 Rust was voted the \"most loved language\" on Stack Overflow for 8 years in a row! Its ownership model ensures memory safety without a garbage collector.",
        "python" =>
            "🐍 Python is named after Monty Python's Flying Circus! It's one of the most versatile languages, used from web dev to AI and data science.",
        "javascript" =>
            "🌐 JavaScript runs on 98% of all websites! Despite the name, it's not related to Java at all.",
        "typescript" =>
            "📘 TypeScript adds static typing to JavaScript, catching errors before you even run the code. Microsoft built it!",
        "go" =>
            "⚡ Go was created at Google by some of the same people who designed C! It's built for speed and simplicity.",
        "java" =>
            "☕ Java's \"Write Once, Run Anywhere\" philosophy means code runs on any device with a JVM. 3 billion devices run Java!",
        "cpp" =>
            "🔧 C++ is over 40 years old and still one of the most powerful languages for systems programming and game development.",
        "c" =>
            "💻 C is the grandfather of modern programming languages. Unix, Linux, Windows, and even Python were written in C!",
    ];

    return $facts[strtolower($language)] ??
        "💡 Every programming language is a tool. The best one is the one that helps you solve the problem at hand!";
}

// ============== DAILY CHALLENGES ==============

function get_daily_challenge($date = null)
{
    global $pdo;

    if (!$date) {
        $date = date("Y-m-d");
    }

    $stmt = $pdo->prepare("
        SELECT dc.*, lang.name as language_name, lang.slug as language_slug, lang.color as language_color
        FROM daily_challenges dc
        JOIN languages lang ON dc.language_id = lang.id
        WHERE dc.date = ? AND dc.is_active = TRUE
    ");
    $stmt->execute([$date]);
    return $stmt->fetch();
}

function create_daily_challenge($data)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO daily_challenges (title, description, language_id, difficulty, challenge_type, starter_code, test_cases, xp_reward, bonus_xp, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE title = VALUES(title)
    ");
    $stmt->execute([
        $data["title"],
        $data["description"],
        $data["language_id"],
        $data["difficulty"],
        $data["challenge_type"],
        $data["starter_code"] ?? "",
        json_encode($data["test_cases"] ?? []),
        $data["xp_reward"] ?? XP_DAILY_CHALLENGE,
        $data["bonus_xp"] ?? 50,
        $data["date"],
    ]);

    return $pdo->lastInsertId();
}

// ============== BADGE FUNCTIONS ==============

function check_and_award_badges($user_id)
{
    global $pdo;

    try {
        $user = get_user_by_id($user_id);
        if (!$user) {
            return [];
        }

        $newly_earned = [];

        $stmt = $pdo->prepare("
            SELECT b.* FROM badges b
            WHERE b.id NOT IN (
                SELECT badge_id FROM user_badges WHERE user_id = ?
            )
        ");
        $stmt->execute([$user_id]);
        $available_badges = $stmt->fetchAll();

        foreach ($available_badges as $badge) {
            $earned = false;

            switch ($badge["requirement_type"]) {
                case "lessons_completed":
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1",
                    );
                    $stmt->execute([$user_id]);
                    $earned =
                        $stmt->fetch()["count"] >= $badge["requirement_value"];
                    break;

                case "xp_earned":
                    $earned =
                        $user["total_xp_earned"] >= $badge["requirement_value"];
                    break;

                case "level_reached":
                    $earned = $user["level"] >= $badge["requirement_value"];
                    break;

                case "streak_days":
                    $earned =
                        $user["current_streak"] >= $badge["requirement_value"];
                    break;

                case "br_wins":
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) as count FROM br_participants WHERE user_id = ? AND is_winner = TRUE",
                    );
                    $stmt->execute([$user_id]);
                    $wins = $stmt->fetch()["count"];
                    $earned = $wins >= $badge["requirement_value"];
                    break;

                case "mini_game_score":
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) as count FROM mini_game_scores WHERE user_id = ? AND completed = TRUE",
                    );
                    $stmt->execute([$user_id]);
                    $earned =
                        $stmt->fetch()["count"] >= $badge["requirement_value"];
                    break;

                case "language_master":
                    $stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT l.language_id) as count
                        FROM user_progress up
                        JOIN lessons l ON up.lesson_id = l.id
                        WHERE up.user_id = ? AND up.completed = 1
                    ");
                    $stmt->execute([$user_id]);
                    $earned =
                        $stmt->fetch()["count"] >= $badge["requirement_value"];
                    break;

                case "rank_achieved":
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) + 1 as user_rank FROM users WHERE xp > ?",
                    );
                    $stmt->execute([$user["xp"]]);
                    $rank = $stmt->fetch()["user_rank"];
                    $earned = $rank <= $badge["requirement_value"];
                    break;

                case "special":
                    $earned = check_special_badge(
                        $user_id,
                        $badge["requirement_value"],
                    );
                    break;
            }

            if ($earned) {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())",
                    );
                    $stmt->execute([$user_id, $badge["id"]]);
                    $newly_earned[] = $badge;

                    create_notification(
                        $user_id,
                        "badge_earned",
                        "New Badge Earned!",
                        "You've earned the '{$badge["name"]}' badge!",
                    );
                } catch (PDOException $e) {
                }
            }
        }

        return $newly_earned;
    } catch (PDOException $e) {
        return [];
    }
}

function check_special_badge($user_id, $special_type)
{
    global $pdo;

    switch ($special_type) {
        case 1: // Early Bird
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1 AND HOUR(completed_at) < 8",
            );
            $stmt->execute([$user_id]);
            return $stmt->fetch()["count"] > 0;

        case 2: // Night Owl
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1 AND HOUR(completed_at) >= 22",
            );
            $stmt->execute([$user_id]);
            return $stmt->fetch()["count"] > 0;

        case 3: // Weekend Warrior
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT CASE WHEN DAYOFWEEK(completed_at) = 1 THEN DATE(completed_at) END) as sundays,
                       COUNT(DISTINCT CASE WHEN DAYOFWEEK(completed_at) = 7 THEN DATE(completed_at) END) as saturdays
                FROM user_progress WHERE user_id = ? AND completed = 1
            ");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch();
            return $stats["sundays"] > 0 && $stats["saturdays"] > 0;

        case 4: // Perfect Week
            $stmt = $pdo->prepare(
                "SELECT DATE(completed_at) as date FROM user_progress WHERE user_id = ? AND completed = 1 ORDER BY completed_at DESC",
            );
            $stmt->execute([$user_id]);
            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $unique_dates = array_unique($dates);
            for ($i = 0; $i <= count($unique_dates) - 7; $i++) {
                $consecutive = true;
                $start_date = new DateTime($unique_dates[$i]);
                for ($j = 1; $j < 7; $j++) {
                    $expected_date = clone $start_date;
                    $expected_date->modify("-{$j} days");
                    if (
                        !in_array(
                            $expected_date->format("Y-m-d"),
                            $unique_dates,
                        )
                    ) {
                        $consecutive = false;
                        break;
                    }
                }
                if ($consecutive) {
                    return true;
                }
            }
            return false;
    }

    return false;
}

// ============== NOTIFICATION FUNCTIONS ==============

function create_notification($user_id, $type, $title, $message, $data = null)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $type,
            $title,
            $message,
            $data ? json_encode($data) : null,
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function get_user_notifications($user_id, $limit = 20, $unread_only = false)
{
    global $pdo;

    $where = "WHERE user_id = ?";
    $params = [$user_id];

    if ($unread_only) {
        $where .= " AND read_at IS NULL";
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM notifications {$where} ORDER BY created_at DESC LIMIT ?",
    );
    $params[] = $limit;
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function mark_notification_read($notification_id, $user_id)
{
    global $pdo;
    $stmt = $pdo->prepare(
        "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?",
    );
    $stmt->execute([$notification_id, $user_id]);
}

function get_unread_notification_count($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL",
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch()["count"];
}

// ============== TIME HELPERS ==============

function time_ago($datetime)
{
    $time = time() - strtotime($datetime);
    if ($time < 60) {
        return "just now";
    }
    if ($time < 3600) {
        return floor($time / 60) . "m ago";
    }
    if ($time < 86400) {
        return floor($time / 3600) . "h ago";
    }
    if ($time < 2592000) {
        return floor($time / 86400) . "d ago";
    }
    if ($time < 31536000) {
        return floor($time / 2592000) . "mo ago";
    }
    return floor($time / 31536000) . "y ago";
}

// ============== UI HELPERS ==============

function get_difficulty_badge($difficulty)
{
    $colors = [
        "beginner" => "bg-green-500/20 text-green-400 border-green-500/30",
        "intermediate" => "bg-blue-500/20 text-blue-400 border-blue-500/30",
        "advanced" => "bg-red-500/20 text-red-400 border-red-500/30",
    ];
    $color = $colors[$difficulty] ?? $colors["beginner"];
    return "<span class=\"px-2 py-1 text-xs rounded-full border {$color}\">" .
        ucfirst($difficulty) .
        "</span>";
}

function get_challenge_type_icon($type)
{
    $icons = [
        "coding" => "fas fa-code",
        "debugging" => "fas fa-bug",
        "optimization" => "fas fa-tachometer-alt",
        "algorithm" => "fas fa-brain",
    ];
    return $icons[$type] ?? "fas fa-code";
}

function get_br_type_icon($type)
{
    $icons = [
        "solo" => "fas fa-user",
        "duo" => "fas fa-user-friends",
        "squad" => "fas fa-users",
    ];
    return $icons[$type] ?? "fas fa-user";
}

function get_mini_game_type_icon($type)
{
    $icons = [
        "code_race" => "fas fa-running",
        "bug_hunt" => "fas fa-bug",
        "refactor_challenge" => "fas fa-wrench",
        "output_prediction" => "fas fa-brain",
        "syntax_speed" => "fas fa-tachometer-alt",
    ];
    return $icons[$type] ?? "fas fa-gamepad";
}

function get_language_select_options($selected_id = null)
{
    $languages = get_languages();
    $html = "";
    foreach ($languages as $lang) {
        $selected = $lang["id"] == $selected_id ? "selected" : "";
        $html .= "<option value=\"{$lang["id"]}\" {$selected}>{$lang["name"]}</option>";
    }
    return $html;
}

function get_avatar_letter($username)
{
    return strtoupper(substr($username, 0, 2));
}

function get_rank_badge($rank)
{
    if ($rank == 1) {
        return '<i class="fas fa-crown text-yellow-400 text-2xl"></i>';
    }
    if ($rank == 2) {
        return '<i class="fas fa-medal text-gray-300 text-2xl"></i>';
    }
    if ($rank == 3) {
        return '<i class="fas fa-award text-orange-400 text-2xl"></i>';
    }
    return "<span class=\"text-lg font-bold text-gray-400\">#{$rank}</span>";
}

// ============== AI LESSON GENERATION ==============

function generate_ai_lesson(
    $language_id,
    $difficulty = "beginner",
    $topic = null,
) {
    global $pdo;

    $lang = get_language_by_id($language_id);
    if (!$lang) {
        return ["error" => "Language not found"];
    }

    $topics = get_lesson_topics($lang["slug"], $difficulty);
    $selected_topic = $topic ?: $topics[array_rand($topics)];

    // Generate complete lesson content using Big Pickle intelligence
    $content = build_lesson_content($lang, $difficulty, $selected_topic);

    // Check if lesson already exists (avoid duplicates)
    $stmt = $pdo->prepare(
        "SELECT id FROM lessons WHERE language_id = ? AND title = ? AND difficulty = ?",
    );
    $stmt->execute([$language_id, $content["title"], $difficulty]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update it with fresh content
        $stmt = $pdo->prepare(
            "UPDATE lessons SET content = ?, code_template = ?, starter_code = ?, expected_output = ?, test_cases = ?, hints = ?, description = ? WHERE id = ?",
        );
        $stmt->execute([
            $content["content"],
            $content["code_template"],
            $content["starter_code"],
            $content["expected_output"],
            json_encode($content["test_cases"]),
            $content["hints"],
            $content["description"],
            $existing["id"],
        ]);
        return [
            "action" => "updated",
            "lesson_id" => $existing["id"],
            "title" => $content["title"],
        ];
    }

    // Insert new lesson
    $stmt = $pdo->prepare(
        "INSERT INTO lessons (language_id, title, description, content, code_template, starter_code, expected_output, test_cases, hints, difficulty, category, xp_reward, order_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    );

    // Get the next order_num for this language
    $stmt2 = $pdo->prepare(
        "SELECT COALESCE(MAX(order_num), 0) + 1 as next_order FROM lessons WHERE language_id = ?",
    );
    $stmt2->execute([$language_id]);
    $order_num = $stmt2->fetch()["next_order"];

    $stmt->execute([
        $language_id,
        $content["title"],
        $content["description"],
        $content["content"],
        $content["code_template"],
        $content["starter_code"],
        $content["expected_output"],
        json_encode($content["test_cases"]),
        $content["hints"],
        $difficulty,
        $content["category"],
        $content["xp_reward"],
        $order_num,
    ]);

    $lesson_id = $pdo->lastInsertId();

    // Create progress entry for all users
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO user_progress (user_id, lesson_id, completed) SELECT id, ?, 0 FROM users",
    );
    $stmt->execute([$lesson_id]);

    return [
        "action" => "created",
        "lesson_id" => $lesson_id,
        "title" => $content["title"],
    ];
}

function get_lesson_topics($language, $difficulty)
{
    static $all_topics = null;
    if ($all_topics === null) {
        $all_topics = [
            "rust" => [
                "beginner" => [
                    "Variables & Mutability",
                    "Data Types",
                    "Functions",
                    "Control Flow",
                    "Ownership Basics",
                    "Strings",
                    "Arrays & Vectors",
                    "Pattern Matching",
                    "Structs",
                    "Enums",
                ],
                "intermediate" => [
                    "Ownership & Borrowing",
                    "Lifetimes",
                    "Traits",
                    "Generics",
                    "Error Handling",
                    "Closures",
                    "Iterators",
                    "Smart Pointers",
                    "Patterns & Matching",
                    "Modules & Crates",
                ],
                "advanced" => [
                    "Unsafe Rust",
                    "Concurrency",
                    "Macros",
                    "FFI",
                    "Advanced Traits",
                    "Advanced Types",
                    "Async/Await",
                    "Pin & Unpin",
                    "Custom Allocators",
                    "DSL Design",
                ],
            ],
            "python" => [
                "beginner" => [
                    "Variables & Data Types",
                    "Strings & Formatting",
                    "Lists & Tuples",
                    "Dictionaries & Sets",
                    "If/Else Statements",
                    "Loops",
                    "Functions",
                    "File I/O",
                    "Exception Handling",
                    "Modules",
                ],
                "intermediate" => [
                    "List Comprehensions",
                    "Generators & Iterators",
                    "Decorators",
                    "Context Managers",
                    "OOP Basics",
                    "Inheritance",
                    "Magic Methods",
                    "Regular Expressions",
                    "JSON & APIs",
                    "Virtual Environments",
                ],
                "advanced" => [
                    "Metaclasses",
                    "Descriptors",
                    "Async/Await",
                    "Multiprocessing",
                    "C Extensions",
                    "Type Hints",
                    "Design Patterns",
                    "Protocols",
                    "Abstract Base Classes",
                    "Performance Optimization",
                ],
            ],
            "javascript" => [
                "beginner" => [
                    "Variables (let/const)",
                    "Data Types",
                    "Functions",
                    "Objects & Arrays",
                    "If/Else & Switch",
                    "Loops",
                    "DOM Manipulation",
                    "Events",
                    "String Methods",
                    "Array Methods",
                ],
                "intermediate" => [
                    "Closures",
                    "Promises & Callbacks",
                    "Async/Await",
                    "Prototypes & Classes",
                    "this Keyword",
                    "Modules (ESM)",
                    "Error Handling",
                    "Fetch API",
                    "Local Storage",
                    "Destructuring",
                ],
                "advanced" => [
                    "Event Loop",
                    "Generators",
                    "Proxies & Reflect",
                    "Web Workers",
                    "Service Workers",
                    "Memory Management",
                    "Performance",
                    "WebAssembly",
                    "Micro-frontends",
                    "Reactive Programming",
                ],
            ],
            "typescript" => [
                "beginner" => [
                    "Basic Types",
                    "Interfaces",
                    "Functions & Types",
                    "Arrays & Tuples",
                    "Union Types",
                    "Type Aliases",
                    "Enums",
                    "Type Assertions",
                    "Literal Types",
                    "Modules",
                ],
                "intermediate" => [
                    "Generics",
                    "Utility Types",
                    "Type Guards",
                    "Conditional Types",
                    "Mapped Types",
                    "Template Literal Types",
                    "Declaration Files",
                    "Namespace",
                    "Mixins",
                    "Decorators",
                ],
                "advanced" => [
                    "Infer Types",
                    "Recursive Types",
                    "Covariance/Contravariance",
                    "Branded Types",
                    "Satisfies Operator",
                    "Variadic Tuple Types",
                    "Module Augmentation",
                    "Type Challenges",
                    "Framework Types",
                    "Builder Pattern",
                ],
            ],
            "go" => [
                "beginner" => [
                    "Variables & Constants",
                    "Data Types",
                    "Functions",
                    "Control Flow",
                    "Arrays & Slices",
                    "Maps",
                    "Structs",
                    "Methods",
                    "Interfaces",
                    "Error Handling",
                ],
                "intermediate" => [
                    "Pointers",
                    "Goroutines",
                    "Channels",
                    "Select Statement",
                    "Packages & Modules",
                    "File I/O",
                    "JSON Encoding",
                    "Testing",
                    "Benchmarks",
                    "Context",
                ],
                "advanced" => [
                    "Reflection",
                    "Code Generation",
                    "Plugin System",
                    "CGO",
                    "Network Programming",
                    "Profiling",
                    "Race Detection",
                    "Compiler Optimizations",
                    "Assembly",
                    "Design Patterns",
                ],
            ],
            "java" => [
                "beginner" => [
                    "Variables & Types",
                    "Operators",
                    "Control Flow",
                    "Arrays",
                    "Methods",
                    "Classes & Objects",
                    "Strings",
                    "Packages",
                    "Exception Handling",
                    "Basic I/O",
                ],
                "intermediate" => [
                    "Inheritance",
                    "Polymorphism",
                    "Abstract Classes",
                    "Interfaces",
                    "Collections",
                    "Generics",
                    "Lambda Expressions",
                    "Streams API",
                    "File I/O (NIO)",
                    "Maven/Gradle",
                ],
                "advanced" => [
                    "Concurrency",
                    "Memory Model",
                    "Reflection",
                    "Annotations",
                    "Dynamic Proxies",
                    "ClassLoaders",
                    "JMX",
                    "Performance Tuning",
                    "JNI",
                    "Module System",
                ],
            ],
            "cpp" => [
                "beginner" => [
                    "Variables & Types",
                    "Control Flow",
                    "Functions",
                    "Arrays & Strings",
                    "Pointers",
                    "References",
                    "Classes & Objects",
                    "Constructors/Destructors",
                    "Inheritance",
                    "Polymorphism",
                ],
                "intermediate" => [
                    "Templates",
                    "STL Containers",
                    "STL Algorithms",
                    "Smart Pointers",
                    "Move Semantics",
                    "Operator Overloading",
                    "Exception Handling",
                    "File I/O (fstream)",
                    "Namespaces",
                    "Type Casting",
                ],
                "advanced" => [
                    "Metaprogramming",
                    "Variadic Templates",
                    "Concepts (C++20)",
                    "Ranges (C++20)",
                    "Coroutines (C++20)",
                    "Multithreading",
                    "Memory Management",
                    "SFINAE",
                    "CRTP",
                    "Plugin Architecture",
                ],
            ],
            "c" => [
                "beginner" => [
                    "Variables & Types",
                    "Control Flow",
                    "Functions",
                    "Arrays",
                    "Pointers",
                    "Strings",
                    "Structs",
                    "File I/O",
                    "Dynamic Memory",
                    "Preprocessor",
                ],
                "intermediate" => [
                    "Function Pointers",
                    "Bit Manipulation",
                    "Variable Arguments",
                    "Unions & Bitfields",
                    "Linked Lists",
                    "Recursion",
                    "Makefiles",
                    "Library Creation",
                    "Error Handling",
                    "Signal Handling",
                ],
                "advanced" => [
                    "Memory Layout",
                    "Inline Assembly",
                    "POSIX APIs",
                    "Socket Programming",
                    "Multi-threading (pthreads)",
                    "Shared Memory",
                    "Kernel Modules",
                    "Compiler Design",
                    "Optimization",
                    "Undefined Behavior",
                ],
            ],
        ];
    }

    $lang_key = strtolower($language);
    if (isset($all_topics[$lang_key][$difficulty])) {
        return $all_topics[$lang_key][$difficulty];
    }
    // Fallback to Rust beginner topics
    return $all_topics["rust"]["beginner"];
}

function build_lesson_content($lang, $difficulty, $topic)
{
    $language = $lang["slug"];
    $lang_name = $lang["name"];

    $xp_map = ["beginner" => 100, "intermediate" => 200, "advanced" => 300];
    $category = strtolower(preg_replace("/[^a-zA-Z0-9]/", "_", $topic));

    // Build code examples based on language and topic
    $code_examples = get_code_examples($language, $topic, $difficulty);
    $exercise = get_code_exercise($language, $topic, $difficulty);

    $title = "$lang_name: $topic";
    $description = "Master $topic in $lang_name with this $difficulty-level lesson.";

    $content = "# $topic\n\n";
    $content .= "## Overview\n\n";
    $content .= "In this lesson, you'll learn about **$topic** in $lang_name. ";
    $content .= "This is a $difficulty-level concept that's essential for building real-world applications.\n\n";
    $content .= "## Key Concepts\n\n";
    $content .= "1. **Definition**: $topic is a fundamental concept in $lang_name programming.\n";
    $content .=
        "2. **Purpose**: It helps you write cleaner, more efficient, and maintainable code.\n";
    $content .= "3. **Usage**: You'll use this in almost every $lang_name program you write.\n\n";
    $content .= "## Code Example\n\n";
    $content .= "Here's a practical example:\n\n";
    $content .= "```$language\n";
    $content .= $code_examples["example"] . "\n";
    $content .= "```\n\n";
    $content .= $code_examples["explanation"] . "\n\n";
    $content .= "## Your Turn!\n\n";
    $content .= $exercise["instruction"] . "\n\n";
    $content .= "**Starter code:**\n\n";

    $hints = implode(
        "\n",
        $exercise["hints"] ?? [
            "Review the example above",
            "Check your syntax carefully",
            "Think about edge cases",
        ],
    );

    return [
        "title" => $title,
        "description" => $description,
        "content" => $content,
        "code_template" => $code_examples["template"],
        "starter_code" => $exercise["starter_code"],
        "expected_output" => $exercise["expected_output"],
        "test_cases" => $exercise["test_cases"] ?? [],
        "hints" => $hints,
        "category" => $category,
        "xp_reward" => $xp_map[$difficulty] ?? 100,
    ];
}

function get_code_examples($language, $topic, $difficulty)
{
    // AI-powered code examples per language/topic - these are templates
    // that generate realistic, compilable code examples
    $examples = [
        "rust" => [
            "Variables & Mutability" => [
                "example" => 'fn main() {
    let x = 5; // immutable
    let mut y = 10; // mutable
    y += x;
    println!("x = {}, y = {}", x, y);
}',
                "explanation" =>
                    "Variables in Rust are immutable by default. Use `mut` to make them mutable. This prevents accidental changes and makes code safer.",
                "template" => 'fn main() {
    // Declare an immutable variable
    let ________ = 5;

    // Declare a mutable variable
    let ________ = 10;

    // Modify the mutable variable
    ________ += ________;

    println!("Result: {}", ________);
}',
            ],
            "Functions" => [
                "example" => 'fn add(x: i32, y: i32) -> i32 {
    x + y
}

fn main() {
    let sum = add(5, 3);
    println!("5 + 3 = {}", sum);
}',
                "explanation" =>
                    "Functions in Rust use `fn` keyword. The last expression is the return value (no semicolon). Parameters and return types are explicitly annotated.",
                "template" => 'fn multiply(a: i32, b: i32) -> i32 {
    // Return the product of a and b

}

fn main() {
    let result = multiply(4, 7);
    println!("4 * 7 = {}", result);
}',
            ],
        ],
        "python" => [
            "Variables & Data Types" => [
                "example" => 'x = 5          # int
y = 3.14        # float
name = "Alice"  # str
is_active = True # bool
print(f"{name} is {x} years old")',
                "explanation" =>
                    "Python is dynamically typed. Variables don't need type declarations. Use `type()` to check the type of any variable.",
                "template" => '# Create variables of different types
name = "________"
age = ________
height = ________
is_student = ________

print(f"Name: {name}")
print(f"Age: {age}")
print(f"Height: {height}")
print(f"Is student: {is_student}")',
            ],
            "Lists & Tuples" => [
                "example" => 'fruits = ["apple", "banana", "cherry"]
fruits.append("date")
print(fruits[0])  # apple
print(len(fruits))  # 4

# Tuple (immutable)
coords = (10, 20)
x, y = coords
print(f"x={x}, y={y}")',
                "explanation" =>
                    "Lists are mutable ordered collections. Tuples are immutable. Both support indexing, slicing, and iteration.",
                "template" => '# Create a list of numbers
numbers = [1, 2, 3, 4, 5]

# Add a number to the list
numbers.________(6)

# Print the first and last elements
print(f"First: {________}")
print(f"Last: {________}")
print(f"Count: {________}")',
            ],
        ],
    ];

    // Check hardcoded examples first (fast path)
    $lang = strtolower($language);
    if (isset($examples[$lang][$topic])) {
        return $examples[$lang][$topic];
    }

    // Try AI-powered generation for unknown language/topic combinations
    $ai_result = ai_generate_code_example($language, $topic, $difficulty);
    if ($ai_result !== null) {
        return $ai_result;
    }

    // Ultimate fallback: language-appropriate default template
    return get_language_fallback_example($language, $topic);
}

function get_language_fallback_example($language, $topic)
{
    $greeting_examples = [
        "rust" => [
            "example" => 'fn main() {
    println!("Hello, World!");
}',
            "explanation" =>
                "This is a basic Rust program. The `fn main()` function is the entry point, and `println!` is a macro that prints to the console.",
            "template" => 'fn main() {
    // Write your code here
    println!("Hello, World!");
}',
        ],
        "python" => [
            "example" => 'print("Hello, World!")',
            "explanation" =>
                "This is a basic Python program. The `print()` function outputs text to the console.",
            "template" => '# Write your code here
print("Hello, World!")',
        ],
        "javascript" => [
            "example" => 'console.log("Hello, World!");',
            "explanation" =>
                "This is a basic JavaScript program. `console.log()` outputs text to the console.",
            "template" => '// Write your code here
console.log("Hello, World!");',
        ],
        "typescript" => [
            "example" => 'const greeting: string = "Hello, World!";
console.log(greeting);',
            "explanation" =>
                "This is a basic TypeScript program. Type annotations are optional but help catch errors at compile time.",
            "template" => '// Write your code here
const greeting: string = "Hello, World!";
console.log(greeting);',
        ],
        "go" => [
            "example" => 'package main

import "fmt"

func main() {
    fmt.Println("Hello, World!")
}',
            "explanation" =>
                "This is a basic Go program. The `package main` declares the package, and `func main()` is the entry point.",
            "template" => 'package main

import "fmt"

func main() {
    // Write your code here
    fmt.Println("Hello, World!")
}',
        ],
        "java" => [
            "example" => 'public class Main {
    public static void main(String[] args) {
        System.out.println("Hello, World!");
    }
}',
            "explanation" =>
                "This is a basic Java program. The class must match the filename, and `main()` is the entry point.",
            "template" => 'public class Main {
    public static void main(String[] args) {
        // Write your code here
        System.out.println("Hello, World!");
    }
}',
        ],
        "cpp" => [
            "example" => '#include <iostream>
using namespace std;
int main() {
    cout << "Hello, World!" << endl;
    return 0;
}',
            "explanation" =>
                "This is a basic C++ program. `#include <iostream>` handles input/output, and `main()` is the entry point.",
            "template" => '#include <iostream>
using namespace std;
int main() {
    // Write your code here
    cout << "Hello, World!" << endl;
    return 0;
}',
        ],
        "c" => [
            "example" => '#include <stdio.h>
int main() {
    printf("Hello, World!");
    return 0;
}',
            "explanation" =>
                "This is a basic C program. `#include <stdio.h>` provides printf, and `main()` is the entry point.",
            "template" => '#include <stdio.h>
int main() {
    // Write your code here
    printf("Hello, World!");
    return 0;
}',
        ],
    ];

    $lang = strtolower($language);
    if (isset($greeting_examples[$lang])) {
        $base = $greeting_examples[$lang];
        // Customize the description for the topic
        $base["explanation"] =
            "This is a basic {$lang} program demonstrating {$topic}. " .
            "Study the pattern and try writing your own version.";
        return $base;
    }

    // Unknown language — return a comment-based template
    return [
        "example" => "// Write your {$language} code here\n// Example for: {$topic}",
        "explanation" => "Write a {$language} program that demonstrates {$topic}.",
        "template" => "// Write your {$language} code here\n// Example for: {$topic}\n",
    ];
}

function ai_generate_code_example($language, $topic, $difficulty)
{
    if (!AI_TUTOR_ENABLED) {
        return null;
    }

    $prompt =
        "Generate a {$difficulty}-level code example in {$language} about \"{$topic}\". " .
        "Return ONLY a valid JSON object with exactly these 3 fields (no markdown, no extra text):\n" .
        "- example: A complete, working {$language} code snippet demonstrating {$topic}\n" .
        "- explanation: A clear 1-2 sentence explanation of what the code does\n" .
        "- template: A fill-in-the-blank version of the example where key parts are replaced with ________ (for students to complete)\n\n" .
        "The code MUST be syntactically correct {$language}.\n" .
        'Return ONLY valid JSON in this format: {"example":"...","explanation":"...","template":"..."}';

    $response = call_opencode_api($prompt, $language);
    if (!$response) {
        return null;
    }

    // Try to extract JSON from the response
    $json = $response;
    // Handle markdown-wrapped JSON
    if (preg_match("/```(?:json)?\s*([\s\S]*?)```/", $response, $m)) {
        $json = $m[1];
    }
    $parsed = json_decode(trim($json), true);
    if (
        $parsed &&
        isset($parsed["example"], $parsed["explanation"], $parsed["template"])
    ) {
        return $parsed;
    }

    return null;
}

function get_code_exercise($language, $topic, $difficulty)
{
    $exercises = [
        "rust" => [
            "Variables & Mutability" => [
                "instruction" =>
                    "Create two variables: an immutable integer `x` with value 10, and a mutable integer `y` with value 20. Update y to be the sum of x and y, then print the result.",
                "starter_code" => 'fn main() {
    // Your code here

}',
                "expected_output" => "y = 30",
                "test_cases" => [["input" => "", "expected" => "y = 30"]],
                "hints" => [
                    "Use `let` for immutable, `let mut` for mutable",
                    "To add: y += x or y = y + x",
                    "Use println! to print: println!(\"y = {}\", y)",
                ],
            ],
            "Functions" => [
                "instruction" =>
                    "Write a function called `square` that takes an i32 and returns its square. Then call it with 6 and print the result.",
                "starter_code" => 'fn square(x: i32) -> i32 {
    // Return x squared

}

fn main() {
    // Call square with 6 and print

}',
                "expected_output" => "36",
                "test_cases" => [["input" => "", "expected" => "36"]],
                "hints" => [
                    "Function signature: fn name(params) -> ReturnType {}",
                    "Use x * x to square",
                    "Print with println!",
                ],
            ],
        ],
        "python" => [
            "Variables & Data Types" => [
                "instruction" =>
                    "Create a variable `message` with the value \"Hello, Python!\" and print it. Then create an integer `count` with value 42 and print both on one line.",
                "starter_code" => '# Your code here
',
                "expected_output" => "Hello, Python!\n42",
                "test_cases" => [
                    ["input" => "", "expected" => "Hello, Python!\n42"],
                ],
                "hints" => [
                    "Use print() to output",
                    "You can pass multiple args: print(a, b)",
                    "Use f-strings: print(f\"{var}\")",
                ],
            ],
        ],
    ];

    // Check hardcoded exercises first
    $lang = strtolower($language);
    if (isset($exercises[$lang][$topic])) {
        return $exercises[$lang][$topic];
    }

    // Try AI-powered generation
    $ai_result = ai_generate_code_exercise($language, $topic, $difficulty);
    if ($ai_result !== null) {
        return $ai_result;
    }

    // Ultimate fallback: language-appropriate default
    return get_language_fallback_exercise($language, $topic);
}

function get_language_fallback_exercise($language, $topic)
{
    $starter_codes = [
        "rust" => 'fn main() {
    // Your implementation here

}',
        "python" => '# Your implementation here
',
        "javascript" => '// Your implementation here
',
        "typescript" => '// Your implementation here
',
        "go" => 'package main

import "fmt"

func main() {
    // Your implementation here

}',
        "java" => 'public class Main {
    public static void main(String[] args) {
        // Your implementation here

    }
}',
        "cpp" => '#include <iostream>
using namespace std;
int main() {
    // Your implementation here
    return 0;
}',
        "c" => '#include <stdio.h>
int main() {
    // Your implementation here
    return 0;
}',
    ];

    $lang = strtolower($language);
    $code =
        $starter_codes[$lang] ?? "// Your {$language} implementation here\n";

    return [
        "instruction" => "Write code that demonstrates {$topic} in {$language}. Create a working example and test it.",
        "starter_code" => $code,
        "expected_output" => "Success!",
        "test_cases" => [["input" => "", "expected" => "Success!"]],
        "hints" => [
            "Review the example code above",
            "Start simple and build up",
            "Test with different values",
        ],
    ];
}

function ai_generate_code_exercise($language, $topic, $difficulty)
{
    if (!AI_TUTOR_ENABLED) {
        return null;
    }

    $prompt =
        "Generate a {$difficulty}-level coding exercise in {$language} about \"{$topic}\". " .
        "Return ONLY a valid JSON object with exactly these 5 fields (no markdown, no extra text):\n" .
        "- instruction: A clear instruction for the student to write code (1-2 sentences)\n" .
        "- starter_code: A {$language} code template with blanks for the student to fill in\n" .
        "- expected_output: The exact expected output when the code runs correctly\n" .
        "- test_cases: An array of test cases, each with \"input\" and \"expected\" fields\n" .
        "- hints: An array of 2-3 helpful hints\n\n" .
        "The starter_code MUST be valid {$language} syntax with placeholders where the student writes code.\n" .
        'Return ONLY valid JSON in this format: {"instruction":"...","starter_code":"...","expected_output":"...","test_cases":[...],"hints":[...]}';

    $response = call_opencode_api($prompt, $language);
    if (!$response) {
        return null;
    }

    // Try to extract JSON from the response
    $json = $response;
    if (preg_match("/```(?:json)?\s*([\s\S]*?)```/", $response, $m)) {
        $json = $m[1];
    }
    $parsed = json_decode(trim($json), true);
    if ($parsed && isset($parsed["instruction"], $parsed["starter_code"])) {
        return $parsed;
    }

    return null;
}

function ensure_lessons_exist($language_id, $count = 5)
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM lessons WHERE language_id = ?",
    );
    $stmt->execute([$language_id]);
    $existing = $stmt->fetch()["count"];

    if ($existing >= $count) {
        return ["existing" => $existing];
    }

    $created = [];
    $needed = $count - $existing;

    $difficulties = ["beginner", "intermediate", "advanced"];

    for ($i = 0; $i < $needed; $i++) {
        $diff = $difficulties[$i % 3];
        $result = generate_ai_lesson($language_id, $diff);
        if (!isset($result["error"])) {
            $created[] = $result;
        }
    }

    return ["created" => count($created), "lessons" => $created];
}

// ============== EXTERNAL CHALLENGE API INTEGRATION ==============

function fetch_external_challenges($language, $count = 5)
{
    $challenges = [];

    // Try Open Trivia DB for coding questions
    try {
        $response = @file_get_contents(
            "https://opentdb.com/api.php?amount={$count}&category=18&type=multiple",
        );
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data["response_code"] === 0) {
                foreach ($data["results"] as $q) {
                    $challenges[] = [
                        "title" =>
                            "Coding Trivia: " . substr($q["question"], 0, 60),
                        "description" => strip_tags($q["question"]),
                        "difficulty" => strtolower($q["difficulty"]),
                        "category" => "trivia",
                        "options" => array_merge(
                            [$q["correct_answer"]],
                            $q["incorrect_answers"],
                        ),
                        "answer" => $q["correct_answer"],
                        "source" => "Open Trivia DB",
                    ];
                }
            }
        }
    } catch (Exception $e) {
    }

    return $challenges;
}

function import_external_challenge($challenge)
{
    global $pdo;

    $lang = get_language_by_slug($challenge["language"] ?? "rust");
    if (!$lang) {
        return ["error" => "Language not found"];
    }

    $difficulty = $challenge["difficulty"] ?? "beginner";
    if (!in_array($difficulty, ["beginner", "intermediate", "advanced"])) {
        $difficulty = "beginner";
    }

    $title = $challenge["title"] ?? "External Challenge";
    $description = $challenge["description"] ?? "";

    // Build content with external question
    $content =
        "# Challenge from " . ($challenge["source"] ?? "External") . "\n\n";
    $content .= "## Question\n\n" . $description . "\n\n";

    if (!empty($challenge["options"])) {
        $content .= "## Options\n\n";
        foreach ($challenge["options"] as $i => $opt) {
            $content .= $i + 1 . ". " . $opt . "\n";
        }
    }

    if (!empty($challenge["answer"])) {
        $content .= "\n**Correct Answer:** " . $challenge["answer"] . "\n";
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM lessons WHERE title = ? AND language_id = ?",
    );
    $stmt->execute([$title, $lang["id"]]);

    if ($stmt->fetch()) {
        return ["action" => "exists"];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO lessons (language_id, title, description, content, difficulty, category, xp_reward, order_num) VALUES (?, ?, ?, ?, ?, 'external', 150, 999)",
    );
    $stmt->execute([$lang["id"], $title, $description, $content, $difficulty]);

    return ["action" => "created", "lesson_id" => $pdo->lastInsertId()];
}
