<?php
try {
    $pdo = new PDO(
        "mysql:host=" .
            DB_HOST .
            ";port=" .
            DB_PORT .
            ";dbname=" .
            DB_NAME .
            ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed"]));
}

// Core tables
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    badges TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bio TEXT DEFAULT NULL,
    github_username VARCHAR(100) DEFAULT NULL,
    twitter_username VARCHAR(100) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    last_activity_date DATE DEFAULT NULL,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    total_xp_earned INT DEFAULT 0,
    preferred_language INT DEFAULT 1,
    show_online_status BOOLEAN DEFAULT TRUE,
    is_online BOOLEAN DEFAULT FALSE,
    public_profile TINYINT(1) DEFAULT 0
);

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

CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT DEFAULT 1,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content TEXT NOT NULL,
    code_template TEXT,
    starter_code TEXT,
    expected_output TEXT,
    test_cases JSON DEFAULT NULL,
    hints TEXT DEFAULT NULL,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    category VARCHAR(50) DEFAULT 'basics',
    xp_reward INT DEFAULT 100,
    order_num INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    lesson_id INT,
    completed BOOLEAN DEFAULT FALSE,
    code_submitted TEXT,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, lesson_id)
);

CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    requirement_type ENUM('lessons_completed','xp_earned','streak_days','level_reached','difficulty_complete','code_shared','daily_lessons','rank_achieved','special','br_wins','mini_game_score','language_master') NOT NULL,
    requirement_value INT NOT NULL,
    badge_group VARCHAR(50) DEFAULT 'achievement'
);

CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('follow','like','badge_earned','level_up','lesson_completed','br_event','mini_game','streak') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS code_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    code TEXT NOT NULL,
    language_id INT DEFAULT 1,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    likes_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS code_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_share_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (code_share_id) REFERENCES code_shares(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, code_share_id)
);

CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
);

-- Battle Royale tables
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

CREATE TABLE IF NOT EXISTS br_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT DEFAULT 0,
    survival_time INT DEFAULT 0,
    kills INT DEFAULT 0,
    eliminated_at TIMESTAMP NULL,
    finished_position INT DEFAULT NULL,
    code_submitted TEXT,
    is_winner BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES br_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (match_id, user_id)
);

-- Mini-games
CREATE TABLE IF NOT EXISTS mini_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('code_race', 'bug_hunt', 'refactor_challenge', 'output_prediction', 'syntax_speed') DEFAULT 'code_race',
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    language_id INT DEFAULT 1,
    game_data JSON NOT NULL,
    xp_reward INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id)
);

CREATE TABLE IF NOT EXISTS mini_game_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    score INT DEFAULT 0,
    time_taken INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES mini_games(id) ON DELETE CASCADE
);

-- AI Tutor
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
// ============== MIGRATIONS: Add missing columns to existing tables ==============
// These run after CREATE TABLE IF NOT EXISTS to handle existing tables that don't have new columns
$migrations = [
    "ALTER TABLE users ADD COLUMN is_online BOOLEAN DEFAULT FALSE",
    "ALTER TABLE users ADD COLUMN current_streak INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN last_activity_date DATE DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN longest_streak INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN total_xp_earned INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN preferred_language INT DEFAULT 1",
    "ALTER TABLE users ADD COLUMN show_online_status BOOLEAN DEFAULT TRUE",
    "ALTER TABLE lessons ADD COLUMN language_id INT DEFAULT 1",
    "ALTER TABLE lessons ADD FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL",
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Column already exists or constraint already added - ignore
    }
}

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
");

// Seed languages if empty
$stmt = $pdo->query("SELECT COUNT(*) as count FROM languages");
if ($stmt->fetch()["count"] == 0) {
    $pdo->exec("
        INSERT INTO languages (name, slug, icon, color, extension, template, docker_image, compiler_command, run_command, sort_order) VALUES
        ('Rust', 'rust', 'fab fa-rust', '#DEA584', '.rs', 'fn main() {\n    println!(\"Hello, World!\");\n}', 'rust:1.70', 'rustc main.rs', './main', 1),
        ('Python', 'python', 'fab fa-python', '#3776AB', '.py', 'print(\"Hello, World!\")', 'python:3.11', 'python3 -m py_compile main.py', 'python3 main.py', 2),
        ('JavaScript', 'javascript', 'fab fa-js', '#F7DF1E', '.js', 'console.log(\"Hello, World!\");', 'node:18', 'node --check main.js', 'node main.js', 3),
        ('TypeScript', 'typescript', 'fab fa-typescript', '#3178C6', '.ts', 'const greeting: string = \"Hello, World!\";\nconsole.log(greeting);', 'node:18', 'npx tsc --noEmit main.ts', 'node main.js', 4),
        ('Go', 'go', 'fab fa-golang', '#00ADD8', '.go', 'package main\n\nimport \"fmt\"\n\nfunc main() {\n    fmt.Println(\"Hello, World!\")\n}', 'golang:1.21', 'go build -o main main.go', './main', 5);
    ");
}

// Seed some badges if empty
$stmt = $pdo->query("SELECT COUNT(*) as count FROM badges");
if ($stmt->fetch()["count"] == 0) {
    $pdo->exec("
        INSERT INTO badges (name, description, icon, requirement_type, requirement_value, badge_group) VALUES
        ('First Steps', 'Complete your first lesson', 'fas fa-baby', 'lessons_completed', 1, 'learning'),
        ('Getting Started', 'Complete 5 lessons', 'fas fa-walking', 'lessons_completed', 5, 'learning'),
        ('Dedicated Learner', 'Complete 10 lessons', 'fas fa-graduation-cap', 'lessons_completed', 10, 'learning'),
        ('Rust Apprentice', 'Complete 15 lessons', 'fas fa-hammer', 'lessons_completed', 15, 'learning'),
        ('Rust Journeyman', 'Complete 20 lessons', 'fas fa-wrench', 'lessons_completed', 20, 'learning'),
        ('XP Hunter', 'Earn 1000 XP', 'fas fa-star', 'xp_earned', 1000, 'xp'),
        ('XP Master', 'Earn 3000 XP', 'fas fa-trophy', 'xp_earned', 3000, 'xp'),
        ('XP Legend', 'Earn 5000 XP', 'fas fa-crown', 'xp_earned', 5000, 'xp'),
        ('XP Overlord', 'Earn 10000 XP', 'fas fa-gem', 'xp_earned', 10000, 'xp'),
        ('Level 5 Warrior', 'Reach level 5', 'fas fa-shield-alt', 'level_reached', 5, 'level'),
        ('Level 10 Champion', 'Reach level 10', 'fas fa-crown', 'level_reached', 10, 'level'),
        ('Level 15 Master', 'Reach level 15', 'fas fa-fire', 'level_reached', 15, 'level'),
        ('Level 20 Legend', 'Reach level 20', 'fas fa-dragon', 'level_reached', 20, 'level'),
        ('Battle Royale Champion', 'Win a Battle Royale match', 'fas fa-chess-king', 'br_wins', 1, 'br'),
        ('BR Top 3', 'Finish in top 3 of a Battle Royale', 'fas fa-medal', 'br_wins', 0, 'br'),
        ('Code Racer', 'Win a Code Race mini-game', 'fas fa-tachometer-alt', 'mini_game_score', 1, 'mini_game'),
        ('Bug Hunter', 'Complete a Bug Hunt challenge', 'fas fa-bug', 'mini_game_score', 0, 'mini_game'),
        ('Language Master', 'Complete lessons in 3 different languages', 'fas fa-language', 'language_master', 3, 'language'),
        ('Polyglot', 'Complete lessons in 5 different languages', 'fas fa-globe', 'language_master', 5, 'language');
    ");
}

// Seed mini-games if empty
$stmt = $pdo->query("SELECT COUNT(*) as count FROM mini_games");
if ($stmt->fetch()["count"] == 0) {
    $games = [
        [
            "title" => "Speed Syntax",
            "description" => "Type the correct syntax as fast as you can!",
            "type" => "syntax_speed",
            "difficulty" => "beginner",
            "game_data" => json_encode([
                "time_limit" => 30,
                "questions" => [
                    [
                        "prompt" => "Print to console in Python",
                        "answer" => 'print("Hello")',
                        "hint" => "Use the print function",
                    ],
                    [
                        "prompt" => "Declare a variable in Rust",
                        "answer" => "let x = 5",
                        "hint" => "Use let keyword",
                    ],
                    [
                        "prompt" => "For loop in JavaScript",
                        "answer" => "for(let i=0;i<10;i++)",
                        "hint" => "Use for keyword",
                    ],
                ],
            ]),
            "xp_reward" => 100,
        ],
        [
            "title" => "Bug Hunt",
            "description" => "Find and fix the bugs in this code!",
            "type" => "bug_hunt",
            "difficulty" => "intermediate",
            "game_data" => json_encode([
                "time_limit" => 120,
                "bugs" => [
                    [
                        "code" => 'prnt("Hello")',
                        "fix" => 'print("Hello")',
                        "hint" => "Check the function name",
                    ],
                    [
                        "code" => 'let x = 5\nx = 6',
                        "fix" => 'let mut x = 5\nx = 6',
                        "hint" => "Variable needs to be mutable",
                    ],
                ],
            ]),
            "xp_reward" => 200,
        ],
        [
            "title" => "Output Prediction",
            "description" => "What does this code output?",
            "type" => "output_prediction",
            "difficulty" => "intermediate",
            "game_data" => json_encode([
                "questions" => [
                    [
                        "code" => "print(2 + 3)",
                        "options" => ["5", "23", "Error", "None"],
                        "correct" => 0,
                    ],
                    [
                        "code" => "console.log(typeof 42)",
                        "options" => [
                            "number",
                            "string",
                            "object",
                            "undefined",
                        ],
                        "correct" => 0,
                    ],
                ],
            ]),
            "xp_reward" => 150,
        ],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO mini_games (title, description, type, difficulty, game_data, xp_reward) VALUES (?, ?, ?, ?, ?, ?)",
    );
    foreach ($games as $game) {
        $stmt->execute([
            $game["title"],
            $game["description"],
            $game["type"],
            $game["difficulty"],
            $game["game_data"],
            $game["xp_reward"],
        ]);
    }
}
