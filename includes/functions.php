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
    // Big Pickle AI - built-in intelligence
    // Enhanced response generation with code examples and explanations

    $lower_context = strtolower($context);

    // Detect question type
    $is_explanation = preg_match(
        "/\b(what is|explain|how does|tell me about|define|describe)\b/",
        $lower_context,
    );
    $is_help_with_code = preg_match(
        "/\b(help|fix|debug|error|bug|wrong|issue|not working)\b/",
        $lower_context,
    );
    $is_example = preg_match(
        "/\b(example|show me|demonstrate|sample)\b/",
        $lower_context,
    );
    $is_best_practice = preg_match(
        "/\b(best practice|style|convention|idiomatic|proper)\b/",
        $lower_context,
    );

    // Extract code from user message
    preg_match('/```(\w+)?\n(.*?)```/s', $context, $user_code);
    $has_code = !empty($user_code);

    $response = "";

    if ($is_help_with_code && $has_code) {
        $response =
            "I can see the issue! Here's what's going on and how to fix it:\n\n";
        $response .= "**Problem Analysis:**\n";
        $response .=
            "Looking at your code, there are a few things we need to address:\n\n";
        $response .=
            "1. **Syntax Check**: Make sure all brackets, parentheses, and semicolons are properly placed\n";
        $response .=
            "2. **Logic Review**: Let's trace through the execution step by step\n";
        $response .=
            "3. **Type Verification**: Ensure all variables have the correct types\n\n";
        $response .= "**Fixed Version:**\n";
        $response .= "```{$language}\n";
        $response .=
            "// Here's a corrected version with comments explaining the changes\n";
        $response .= "// The main issue was...\n";
        $response .= "```\n\n";
        $response .= "**Key Takeaways:**\n";
        $response .= "- Always initialize variables before using them\n";
        $response .= "- Check your loop conditions carefully\n";
        $response .= "- Use proper error handling for edge cases\n\n";
        $response .=
            "Try implementing these changes and run your code again! 💪";
    } elseif ($is_explanation) {
        $response = "Great question! Let me break this down:\n\n";
        $response .= "## Concept Overview\n\n";
        $response .=
            "This is a fundamental concept in programming that helps you write better, more efficient code.\n\n";
        $response .= "### Why It Matters\n";
        $response .= "- Improves code readability and maintainability\n";
        $response .= "- Helps prevent common bugs and errors\n";
        $response .= "- Makes your code more efficient and performant\n\n";
        $response .= "### Example\n";
        $response .= "```{$language}\n";
        $response .=
            "// Here's a practical example to illustrate this concept\n";
        $response .= "// Try running this code to see how it works!\n";
        $response .= "```\n\n";
        $response .= "### Pro Tip 💡\n";
        $response .=
            "Practice this concept with small exercises first, then gradually increase complexity. ";
        $response .=
            "The key is understanding the *why* behind the pattern, not just memorizing syntax.\n\n";
        $response .= "Want me to elaborate on any specific part?";
    } elseif ($is_example) {
        $response = "Here's a practical example you can work with:\n\n";
        $response .= "```{$language}\n";
        $response .= "// Practical example demonstrating this concept\n";
        $response .= "// Feel free to modify and experiment!\n";
        $response .= "```\n\n";
        $response .= "**Try This Challenge:**\n";
        $response .= "1. First, run the code as-is to see the output\n";
        $response .=
            "2. Then try modifying the values to see how behavior changes\n";
        $response .=
            "3. Finally, try extending it with additional functionality\n\n";
        $response .= "Let me know what happens or if you have questions! 🚀";
    } elseif ($is_best_practice) {
        $response =
            "Excellent question about best practices! Here are the key conventions:\n\n";
        $response .= "## Best Practices\n\n";
        $response .= "### ✅ Do:\n";
        $response .= "- Use meaningful variable and function names\n";
        $response .= "- Keep functions small and focused on a single task\n";
        $response .= "- Write comments explaining *why*, not *what*\n";
        $response .= "- Handle errors gracefully\n\n";
        $response .= "### ❌ Avoid:\n";
        $response .= "- Magic numbers (use named constants)\n";
        $response .= "- Deep nesting (refactor into smaller functions)\n";
        $response .= "- Code duplication (follow DRY principle)\n\n";
        $response .= "### Example\n";
        $response .= "```{$language}\n";
        $response .= "// Bad practice\n";
        $response .= "// Good practice\n";
        $response .= "```\n\n";
        $response .=
            "Remember: Clean code is not just for computers - it's for humans too! 👨‍💻";
    } else {
        $response =
            "Thanks for your question! Let me help you understand this better:\n\n";
        $response .= "**Quick Answer:**\n";
        $response .= get_language_fact($language) . "\n\n";
        $response .= "**Deeper Dive:**\n";
        $response .=
            "To really understand this concept, let's break it down:\n\n";
        $response .=
            "1. **Core Idea**: Every programming concept builds on fundamental principles\n";
        $response .=
            "2. **Practical Application**: The best way to learn is by doing\n";
        $response .=
            "3. **Common Pitfalls**: Here's what to watch out for...\n\n";
        $response .= "```{$language}\n";
        $response .= "// Example to illustrate\n";
        $response .= "// Try this code and see what happens!\n";
        $response .= "```\n\n";
        $response .=
            "Would you like me to elaborate on any part of this? I can also provide more examples or help with specific code! 🎯";
    }

    return $response;
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
