<?php
require_once "../config.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$language_id = (int) ($_GET["language"] ?? 0);
$limit = min(100, max(1, (int) ($_GET["limit"] ?? 50)));

try {
    if ($language_id > 0) {
        $leaderboard = get_language_leaderboard($language_id, $limit);
    } else {
        $leaderboard = get_leaderboard($limit);
    }

    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT
            u.username,
            l.title as lesson_title,
            l.difficulty,
            l.xp_reward,
            lang.name as language_name,
            lang.icon as language_icon,
            lang.color as language_color,
            up.completed_at,
            'lesson_completed' as activity_type
        FROM user_progress up
        JOIN users u ON up.user_id = u.id
        JOIN lessons l ON up.lesson_id = l.id
        JOIN languages lang ON l.language_id = lang.id
        WHERE up.completed = 1 AND up.completed_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY up.completed_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

    // Get weekly champions
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.username, u.xp, u.level,
            COUNT(DISTINCT up.lesson_id) as weekly_lessons,
            SUM(l.xp_reward) as weekly_xp
        FROM users u
        JOIN user_progress up ON u.id = up.user_id
        JOIN lessons l ON up.lesson_id = l.id
        WHERE up.completed = 1 AND up.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY u.id, u.username, u.xp, u.level
        HAVING weekly_lessons > 0
        ORDER BY weekly_xp DESC, weekly_lessons DESC
        LIMIT 5
    ");
    $stmt->execute();
    $weekly_champions = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "leaderboard" => $leaderboard,
        "recent_activity" => $recent_activity,
        "weekly_champions" => $weekly_champions,
        "language_id" => $language_id,
        "timestamp" => time(),
        "version" => APP_VERSION,
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to fetch leaderboard data",
    ]);
}
