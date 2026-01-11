<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Get leaderboard data with real-time stats
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.xp,
            u.level,
            u.created_at,
            COUNT(DISTINCT up.lesson_id) as lessons_completed,
            COUNT(DISTINCT CASE WHEN up.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN up.lesson_id END) as weekly_lessons,
            COUNT(DISTINCT CASE WHEN up.completed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN up.lesson_id END) as daily_lessons,
            MAX(up.completed_at) as last_activity,
            (SELECT COUNT(*) + 1 FROM users u2 WHERE u2.xp > u.xp) as rank_position
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id AND up.completed = 1
        GROUP BY u.id, u.username, u.xp, u.level, u.created_at
        ORDER BY u.xp DESC, u.created_at ASC
        LIMIT 50
    ");
    $stmt->execute();
    $leaderboard = $stmt->fetchAll();

    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            l.title as lesson_title,
            l.difficulty,
            l.xp_reward,
            up.completed_at,
            'lesson_completed' as activity_type
        FROM user_progress up
        JOIN users u ON up.user_id = u.id
        JOIN lessons l ON up.lesson_id = l.id
        WHERE up.completed = 1 AND up.completed_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY up.completed_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

    // Get weekly champions
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.xp,
            COUNT(DISTINCT up.lesson_id) as weekly_lessons,
            SUM(l.xp_reward) as weekly_xp
        FROM users u
        JOIN user_progress up ON u.id = up.user_id
        JOIN lessons l ON up.lesson_id = l.id
        WHERE up.completed = 1 AND up.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY u.id, u.username, u.xp
        HAVING weekly_lessons > 0
        ORDER BY weekly_xp DESC, weekly_lessons DESC
        LIMIT 5
    ");
    $stmt->execute();
    $weekly_champions = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard,
        'recent_activity' => $recent_activity,
        'weekly_champions' => $weekly_champions,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch leaderboard data'
    ]);
}
?>