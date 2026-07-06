<?php
// Database configuration
define("DB_HOST", "db.fr-pari1.bengt.wasmernet.com");
define("DB_NAME", "rustnite");
define("DB_USER", "3ac2b01570198000841f60ead8ad");
define("DB_PASS", "06963ac2-b015-725b-8000-620e12cfcb0e");
define("DB_PORT", "10272");

// App configuration
define("APP_NAME", "Rustnite");
define("APP_URL", "https://rustnite.wasmer.app");
define("APP_VERSION", "2.0.0");
define("APP_TAGLINE", "Battle-Royale Coding Arena");
define(
    "PAYPAL_DONATE_URL",
    "https://www.paypal.com/donate/?hosted_button_id=RKBHLNTG326DA",
);

// Security
define("HASH_ALGO", PASSWORD_DEFAULT);
define("SESSION_LIFETIME", 3600 * 24 * 30); // 30 days

// XP and leveling system
define("XP_PER_LESSON", 100);
define("XP_PER_LEVEL", 1000);
define("XP_BATTLE_ROYALE_WIN", 500);
define("XP_BATTLE_ROYALE_PARTICIPATE", 50);
define("XP_MINI_GAME_WIN", 100);
define("XP_DAILY_CHALLENGE", 200);
define("XP_STREAK_BONUS", 50);
define("XP_AI_TUTOR_QUESTION", 10);

// Battle Royale settings
define("BR_DEFAULT_MAX_PLAYERS", 50);
define("BR_DEFAULT_TIME_LIMIT", 15);
define("BR_MIN_PLAYERS_TO_START", 2);

// AI Tutor settings
define("AI_TUTOR_ENABLED", true);
define("AI_TUTOR_MODEL", "big-pickle");
define("AI_TUTOR_MAX_TOKENS", 1024);
define("AI_TUTOR_TEMPERATURE", 0.7);

// OpenCode API Configuration
// Get your API key from https://opencode.com/settings/api
// Leave OPENCODE_API_KEY empty to use built-in Big Pickle AI responses (fallback mode)
// The built-in fallback is context-aware and doesn't need an API key
define(
    "OPENCODE_API_KEY",
    "sk-A4roVvunrQ1woG7zQFWIUO0XIUIV46vFEDib6Ezh2zccwWs5EbnjukJv5hzu6Uby",
);
define("OPENCODE_API_URL", "https://opencode.com/api/chat"); // OpenCode API endpoint

// Supported languages
define("DEFAULT_LANGUAGE", 1); // Rust
$SUPPORTED_LANGUAGES = [
    1 => [
        "name" => "Rust",
        "slug" => "rust",
        "icon" => "fab fa-rust",
        "color" => "#DEA584",
    ],
    2 => [
        "name" => "Python",
        "slug" => "python",
        "icon" => "fab fa-python",
        "color" => "#3776AB",
    ],
    3 => [
        "name" => "JavaScript",
        "slug" => "javascript",
        "icon" => "fab fa-js",
        "color" => "#F7DF1E",
    ],
    4 => [
        "name" => "TypeScript",
        "slug" => "typescript",
        "icon" => "fab fa-typescript",
        "color" => "#3178C6",
    ],
    5 => [
        "name" => "Go",
        "slug" => "go",
        "icon" => "fab fa-golang",
        "color" => "#00ADD8",
    ],
    6 => [
        "name" => "Java",
        "slug" => "java",
        "icon" => "fab fa-java",
        "color" => "#ED8B00",
    ],
    7 => [
        "name" => "C++",
        "slug" => "cpp",
        "icon" => "fas fa-copyright",
        "color" => "#00599C",
    ],
    8 => [
        "name" => "C",
        "slug" => "c",
        "icon" => "fas fa-copyright",
        "color" => "#A8B9CC",
    ],
];

// GitHub OAuth
// Create a GitHub OAuth App at https://github.com/settings/developers
// Set callback URL to: https://rustnite.wasmer.app/index.php?page=login&github_callback=1
define("GITHUB_CLIENT_ID", ""); // <-- SET YOUR GITHUB CLIENT ID
define("GITHUB_CLIENT_SECRET", ""); // <-- SET YOUR GITHUB CLIENT SECRET
define(
    "GITHUB_REDIRECT_URI",
    APP_URL . "/index.php?page=login&github_callback=1",
);

// Error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);
