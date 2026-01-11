-- Migration script to add missing tables for enhanced features
-- Run this SQL in your database to add the new tables

-- Create user_badges table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user (user_id),
    INDEX idx_badge (badge_id)
);

-- Add social features to users table (only if columns don't exist)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS github_username VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS twitter_username VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS public_profile TINYINT(1) DEFAULT 0;

-- Create code sharing table
CREATE TABLE IF NOT EXISTS code_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    code TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    likes_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_lesson (lesson_id)
);

-- Create code likes table
CREATE TABLE IF NOT EXISTS code_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_share_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (code_share_id) REFERENCES code_shares(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, code_share_id)
);

-- Create user following system
CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('follow', 'like', 'badge_earned', 'level_up', 'lesson_completed') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_created (created_at)
);

-- Update badges table with new achievements (clear existing and add new ones)
DELETE FROM badges;

-- Enhanced badges with more variety
INSERT INTO badges (name, description, icon, requirement_type, requirement_value) VALUES
-- Learning Milestones
('First Steps', 'Complete your first lesson', 'fas fa-baby', 'lessons_completed', 1),
('Getting Started', 'Complete 5 lessons', 'fas fa-walking', 'lessons_completed', 5),
('Dedicated Learner', 'Complete 10 lessons', 'fas fa-graduation-cap', 'lessons_completed', 10),
('Rust Apprentice', 'Complete 15 lessons', 'fas fa-hammer', 'lessons_completed', 15),
('Rust Journeyman', 'Complete 20 lessons', 'fas fa-wrench', 'lessons_completed', 20),

-- XP Achievements
('XP Hunter', 'Earn 1000 XP', 'fas fa-star', 'xp_earned', 1000),
('XP Master', 'Earn 3000 XP', 'fas fa-trophy', 'xp_earned', 3000),
('XP Legend', 'Earn 5000 XP', 'fas fa-crown', 'xp_earned', 5000),
('XP Overlord', 'Earn 10000 XP', 'fas fa-gem', 'xp_earned', 10000),

-- Level Achievements
('Level 5 Warrior', 'Reach level 5', 'fas fa-shield-alt', 'level_reached', 5),
('Level 10 Champion', 'Reach level 10', 'fas fa-crown', 'level_reached', 10),
('Level 15 Master', 'Reach level 15', 'fas fa-fire', 'level_reached', 15),
('Level 20 Legend', 'Reach level 20', 'fas fa-dragon', 'level_reached', 20),

-- Streak Achievements
('Consistent Learner', 'Maintain a 3-day learning streak', 'fas fa-calendar-check', 'streak_days', 3),
('Dedicated Student', 'Maintain a 7-day learning streak', 'fas fa-fire', 'streak_days', 7),
('Learning Machine', 'Maintain a 14-day learning streak', 'fas fa-bolt', 'streak_days', 14),
('Unstoppable Force', 'Maintain a 30-day learning streak', 'fas fa-rocket', 'streak_days', 30),

-- Difficulty Achievements
('Beginner Graduate', 'Complete all beginner lessons', 'fas fa-seedling', 'difficulty_complete', 1),
('Intermediate Expert', 'Complete all intermediate lessons', 'fas fa-tree', 'difficulty_complete', 2),
('Advanced Master', 'Complete all advanced lessons', 'fas fa-mountain', 'difficulty_complete', 3),

-- Social Achievements
('Code Sharer', 'Share your first code snippet', 'fas fa-share-alt', 'code_shared', 1),
('Helpful Coder', 'Share 5 code snippets', 'fas fa-hands-helping', 'code_shared', 5),
('Community Leader', 'Share 10 code snippets', 'fas fa-users', 'code_shared', 10),

-- Speed Achievements
('Quick Learner', 'Complete 3 lessons in one day', 'fas fa-tachometer-alt', 'daily_lessons', 3),
('Speed Demon', 'Complete 5 lessons in one day', 'fas fa-lightning-bolt', 'daily_lessons', 5),
('Learning Tornado', 'Complete 10 lessons in one day', 'fas fa-tornado', 'daily_lessons', 10),

-- Special Achievements
('Early Bird', 'Complete a lesson before 8 AM', 'fas fa-sun', 'special', 1),
('Night Owl', 'Complete a lesson after 10 PM', 'fas fa-moon', 'special', 2),
('Weekend Warrior', 'Complete lessons on both Saturday and Sunday', 'fas fa-calendar-weekend', 'special', 3),
('Perfect Week', 'Complete at least one lesson every day for a week', 'fas fa-star-of-life', 'special', 4),

-- Rank Achievements
('Top 100', 'Reach top 100 on the leaderboard', 'fas fa-medal', 'rank_achieved', 100),
('Top 50', 'Reach top 50 on the leaderboard', 'fas fa-award', 'rank_achieved', 50),
('Top 10', 'Reach top 10 on the leaderboard', 'fas fa-trophy', 'rank_achieved', 10),
('Elite Rustacean', 'Reach top 5 on the leaderboard', 'fas fa-crown', 'rank_achieved', 5),
('Rust Champion', 'Reach #1 on the leaderboard', 'fas fa-chess-king', 'rank_achieved', 1);