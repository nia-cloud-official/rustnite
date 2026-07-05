-- Rustnite v2: Multi-Language + Battle Royale + Gaming Migration
-- Run this to upgrade from v1 to v2

-- Languages table
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(20) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'fas fa-code',
    color VARCHAR(20) DEFAULT '#FF6B35',
    extension VARCHAR(10) NOT NULL DEFAULT '.rs',
    template TEXT,
    docker_image VARCHAR(100),
    compiler_command VARCHAR(255),
    run_command VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO languages (name, slug, icon, color, extension, template, docker_image, compiler_command, run_command, sort_order) VALUES
('Rust', 'rust', 'fab fa-rust', '#DEA584', '.rs', 'fn main() {\n    println!("Hello, World!");\n}', 'rust:1.70', 'rustc main.rs', './main', 1),
('Python', 'python', 'fab fa-python', '#3776AB', '.py', 'print("Hello, World!")', 'python:3.11', 'python3 -m py_compile main.py', 'python3 main.py', 2),
('JavaScript', 'javascript', 'fab fa-js', '#F7DF1E', '.js', 'console.log("Hello, World!");', 'node:18', 'node --check main.js', 'node main.js', 3),
('TypeScript', 'typescript', 'fab fa-typescript', '#3178C6', '.ts', 'const greeting: string = "Hello, World!";\nconsole.log(greeting);', 'node:18', 'npx tsc --noEmit main.ts', 'node main.js', 4),
('Go', 'go', 'fab fa-golang', '#00ADD8', '.go', 'package main\n\nimport "fmt"\n\nfunc main() {\n    fmt.Println("Hello, World!")\n}', 'golang:1.21', 'go build -o main main.go', './main', 5),
('Java', 'java', 'fab fa-java', '#ED8B00', '.java', 'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n    }\n}', 'openjdk:17', 'javac Main.java', 'java Main', 6),
('C++', 'cpp', 'fas fa-copyright', '#00599C', '.cpp', '#include <iostream>\n\nint main() {\n    std::cout << "Hello, World!" << std::endl;\n    return 0;\n}', 'gcc:12', 'g++ -o main main.cpp', './main', 7),
('C', 'c', 'fas fa-copyright', '#A8B9CC', '.c', '#include <stdio.h>\n\nint main() {\n    printf("Hello, World!\\n");\n    return 0;\n}', 'gcc:12', 'gcc -o main main.c', './main', 8);

-- Add language_id to lessons
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS language_id INT DEFAULT 1 AFTER id;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS test_cases JSON DEFAULT NULL AFTER expected_output;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS hints TEXT DEFAULT NULL AFTER content;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS starter_code TEXT DEFAULT NULL AFTER code_template;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'basics' AFTER difficulty;

-- Battle Royale: matches table
CREATE TABLE IF NOT EXISTS br_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('solo', 'duo', 'squad') DEFAULT 'solo',
    status ENUM('lobby', 'in_progress', 'completed', 'cancelled') DEFAULT 'lobby',
    language_id INT DEFAULT 1,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    max_players INT DEFAULT 50,
    time_limit_minutes INT DEFAULT 15,
    challenge_description TEXT,
    starter_code TEXT,
    expected_output TEXT,
    created_by INT,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- BR Match participants
CREATE TABLE IF NOT EXISTS br_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT DEFAULT 0,
    survival_time INT DEFAULT 0, -- seconds survived
    kills INT DEFAULT 0, -- challenges solved
    eliminated_at TIMESTAMP NULL,
    finished_position INT DEFAULT NULL,
    code_submitted TEXT,
    is_winner BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES br_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (match_id, user_id)
);

-- Mini-games table
CREATE TABLE IF NOT EXISTS mini_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('code_race', 'bug_hunt', 'refactor_challenge', 'output_prediction', 'syntax_speed') DEFAULT 'code_race',
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    game_data JSON NOT NULL,
    xp_reward INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Mini-game scores
CREATE TABLE IF NOT EXISTS mini_game_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    score INT DEFAULT 0,
    time_taken INT DEFAULT 0, -- seconds
    completed BOOLEAN DEFAULT FALSE,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES mini_games(id) ON DELETE CASCADE
);

-- AI Tutor conversations
CREATE TABLE IF NOT EXISTS ai_chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT 'New Chat',
    language VARCHAR(20) DEFAULT 'rust',
    context TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ai_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    code_blocks JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES ai_chats(id) ON DELETE CASCADE
);

-- Daily challenges
CREATE TABLE IF NOT EXISTS daily_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    language_id INT DEFAULT 1,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    challenge_type ENUM('coding', 'debugging', 'optimization', 'algorithm') DEFAULT 'coding',
    starter_code TEXT,
    test_cases JSON,
    xp_reward INT DEFAULT 200,
    bonus_xp INT DEFAULT 50,
    date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY unique_date (date)
);

-- User streaks enhanced
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity_date DATE DEFAULT NULL AFTER location;
ALTER TABLE users ADD COLUMN IF NOT EXISTS current_streak INT DEFAULT 0 AFTER last_activity_date;
ALTER TABLE users ADD COLUMN IF NOT EXISTS longest_streak INT DEFAULT 0 AFTER current_streak;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_xp_earned INT DEFAULT 0 AFTER longest_streak;
ALTER TABLE users ADD COLUMN IF NOT EXISTS preferred_language INT DEFAULT 1 AFTER total_xp_earned;
ALTER TABLE users ADD COLUMN IF NOT EXISTS show_online_status BOOLEAN DEFAULT TRUE AFTER preferred_language;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online BOOLEAN DEFAULT FALSE AFTER show_online_status;
ALTER TABLE users ADD FOREIGN KEY (preferred_language) REFERENCES languages(id);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_br_matches_status ON br_matches(status);
CREATE INDEX IF NOT EXISTS idx_br_participants_match ON br_participants(match_id, score);
CREATE INDEX IF NOT EXISTS idx_mini_game_scores_user ON mini_game_scores(user_id, score);
CREATE INDEX IF NOT EXISTS idx_ai_chats_user ON ai_chats(user_id, updated_at);
CREATE INDEX IF NOT EXISTS idx_ai_messages_chat ON ai_messages(chat_id, created_at);
CREATE INDEX IF NOT EXISTS idx_daily_challenges_date ON daily_challenges(date);
CREATE INDEX IF NOT EXISTS idx_lessons_language ON lessons(language_id);
