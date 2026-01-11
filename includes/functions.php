<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_level($xp) {
    return floor($xp / XP_PER_LEVEL) + 1;
}

function get_xp_for_next_level($current_xp) {
    $current_level = get_user_level($current_xp);
    return $current_level * XP_PER_LEVEL;
}

function add_xp($user_id, $xp) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
    $stmt->execute([$xp, $user_id]);
    
    // Update level
    $user = get_user_by_id($user_id);
    $new_level = get_user_level($user['xp']);
    if ($new_level > $user['level']) {
        $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);
        return $new_level; // Level up!
    }
    return false;
}

function get_leaderboard($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, xp, level FROM users ORDER BY xp DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function get_user_progress($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.*, up.completed, up.completed_at 
        FROM lessons l 
        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
        ORDER BY l.order_num
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function complete_lesson($user_id, $lesson_id, $code) {
    global $pdo;
    
    // Check if already completed
    $stmt = $pdo->prepare("SELECT completed FROM user_progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $progress = $stmt->fetch();
    
    if ($progress && $progress['completed']) {
        return false; // Already completed
    }
    
    // Get lesson XP reward
    $stmt = $pdo->prepare("SELECT xp_reward FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) return false;
    
    // Mark as completed
    $stmt = $pdo->prepare("
        INSERT INTO user_progress (user_id, lesson_id, completed, code_submitted, completed_at) 
        VALUES (?, ?, 1, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        completed = 1, code_submitted = ?, completed_at = NOW()
    ");
    $stmt->execute([$user_id, $lesson_id, $code, $code]);
    
    // Add XP
    $level_up = add_xp($user_id, $lesson['xp_reward']);
    
    // Check for new badges
    $new_badges = check_and_award_badges($user_id);
    
    // Create lesson completion notification
    create_notification($user_id, 'lesson_completed', 'Lesson Completed!', 
        "You've completed a lesson and earned {$lesson['xp_reward']} XP!");
    
    return [
        'xp_earned' => $lesson['xp_reward'],
        'level_up' => $level_up,
        'new_badges' => $new_badges
    ];
}

function get_lesson_by_id($lesson_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch();
}

function is_lesson_completed($user_id, $lesson_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT completed FROM user_progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $result = $stmt->fetch();
    return $result ? $result['completed'] : false;
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    return floor($time/31536000) . 'y ago';
}

function check_and_award_badges($user_id) {
    global $pdo;
    
    try {
        $user = get_user_by_id($user_id);
        $newly_earned = [];
        
        // Get all badges user hasn't earned yet
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
            
            switch ($badge['requirement_type']) {
                case 'lessons_completed':
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1");
                    $stmt->execute([$user_id]);
                    $count = $stmt->fetch()['count'];
                    $earned = $count >= $badge['requirement_value'];
                    break;
                    
                case 'xp_earned':
                    $earned = $user['xp'] >= $badge['requirement_value'];
                    break;
                    
                case 'level_reached':
                    $earned = $user['level'] >= $badge['requirement_value'];
                    break;
                    
                case 'streak_days':
                    $streak = calculate_learning_streak($user_id);
                    $earned = $streak >= $badge['requirement_value'];
                    break;
                    
                case 'difficulty_complete':
                    $difficulties = ['beginner', 'intermediate', 'advanced'];
                    $target_difficulty = $difficulties[$badge['requirement_value'] - 1];
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total,
                            COUNT(CASE WHEN up.completed = 1 THEN 1 END) as completed
                        FROM lessons l
                        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                        WHERE l.difficulty = ?
                    ");
                    $stmt->execute([$user_id, $target_difficulty]);
                    $stats = $stmt->fetch();
                    $earned = $stats['total'] > 0 && $stats['completed'] == $stats['total'];
                    break;
                    
                case 'code_shared':
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM code_shares WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $count = $stmt->fetch()['count'];
                        $earned = $count >= $badge['requirement_value'];
                    } catch (PDOException $e) {
                        $earned = false; // Table doesn't exist
                    }
                    break;
                    
                case 'daily_lessons':
                    $stmt = $pdo->prepare("
                        SELECT MAX(daily_count) as max_daily
                        FROM (
                            SELECT DATE(completed_at) as date, COUNT(*) as daily_count
                            FROM user_progress 
                            WHERE user_id = ? AND completed = 1
                            GROUP BY DATE(completed_at)
                        ) daily_stats
                    ");
                    $stmt->execute([$user_id]);
                    $max_daily = $stmt->fetch()['max_daily'] ?? 0;
                    $earned = $max_daily >= $badge['requirement_value'];
                    break;
                    
                case 'rank_achieved':
                    $stmt = $pdo->prepare("SELECT COUNT(*) + 1 as `user_rank` FROM users WHERE xp > ?");
                    $stmt->execute([$user['xp']]);
                    $rank = $stmt->fetch()['user_rank'];
                    $earned = $rank <= $badge['requirement_value'];
                    break;
                    
                case 'special':
                    $earned = check_special_badge($user_id, $badge['requirement_value']);
                    break;
            }
            
            if ($earned) {
                // Award the badge
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$user_id, $badge['id']]);
                    $newly_earned[] = $badge;
                    
                    // Create notification
                    create_notification($user_id, 'badge_earned', 'New Badge Earned!', 
                        "You've earned the '{$badge['name']}' badge!", 
                        json_encode(['badge_id' => $badge['id']])
                    );
                } catch (PDOException $e) {
                    // Table doesn't exist, skip badge awarding
                }
            }
        }
        
        return $newly_earned;
    } catch (PDOException $e) {
        // Tables don't exist yet, return empty array
        return [];
    }
}

function calculate_learning_streak($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT DATE(completed_at) as completion_date 
        FROM user_progress 
        WHERE user_id = ? AND completed = 1 
        ORDER BY completed_at DESC
    ");
    $stmt->execute([$user_id]);
    $completion_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($completion_dates)) {
        return 0;
    }
    
    $current_streak = 0;
    $today = new DateTime();
    $yesterday = new DateTime('-1 day');
    
    $last_completion = new DateTime($completion_dates[0]);
    
    if ($last_completion->format('Y-m-d') === $today->format('Y-m-d') || 
        $last_completion->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        
        $current_streak = 1;
        $check_date = clone $last_completion;
        
        for ($i = 1; $i < count($completion_dates); $i++) {
            $prev_date = new DateTime($completion_dates[$i]);
            $check_date->modify('-1 day');
            
            if ($prev_date->format('Y-m-d') === $check_date->format('Y-m-d')) {
                $current_streak++;
            } else {
                break;
            }
        }
    }
    
    return $current_streak;
}

function check_special_badge($user_id, $special_type) {
    global $pdo;
    
    switch ($special_type) {
        case 1: // Early Bird - lesson before 8 AM
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM user_progress 
                WHERE user_id = ? AND completed = 1 
                AND HOUR(completed_at) < 8
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch()['count'] > 0;
            
        case 2: // Night Owl - lesson after 10 PM
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM user_progress 
                WHERE user_id = ? AND completed = 1 
                AND HOUR(completed_at) >= 22
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch()['count'] > 0;
            
        case 3: // Weekend Warrior - lessons on both Saturday and Sunday
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT CASE WHEN DAYOFWEEK(completed_at) = 1 THEN DATE(completed_at) END) as sundays,
                    COUNT(DISTINCT CASE WHEN DAYOFWEEK(completed_at) = 7 THEN DATE(completed_at) END) as saturdays
                FROM user_progress 
                WHERE user_id = ? AND completed = 1
            ");
            $stmt->execute([$user_id]);
            $weekend_stats = $stmt->fetch();
            return $weekend_stats['sundays'] > 0 && $weekend_stats['saturdays'] > 0;
            
        case 4: // Perfect Week - lesson every day for a week
            $stmt = $pdo->prepare("
                SELECT DATE(completed_at) as date
                FROM user_progress 
                WHERE user_id = ? AND completed = 1 
                ORDER BY completed_at DESC
            ");
            $stmt->execute([$user_id]);
            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check for 7 consecutive days
            $unique_dates = array_unique($dates);
            for ($i = 0; $i <= count($unique_dates) - 7; $i++) {
                $consecutive = true;
                $start_date = new DateTime($unique_dates[$i]);
                
                for ($j = 1; $j < 7; $j++) {
                    $expected_date = clone $start_date;
                    $expected_date->modify("-{$j} days");
                    
                    if (!in_array($expected_date->format('Y-m-d'), $unique_dates)) {
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

function create_notification($user_id, $type, $title, $message, $data = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $type, $title, $message, $data]);
    } catch (PDOException $e) {
        // Table doesn't exist yet, skip notification
        return false;
    }
}

function get_user_notifications($user_id, $limit = 10, $unread_only = false) {
    global $pdo;
    
    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $where_clause .= " AND read_at IS NULL";
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        {$where_clause}
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $params[] = $limit;
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function mark_notification_read($notification_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET read_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$notification_id, $user_id]);
}