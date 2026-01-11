<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'rustnite');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// App configuration
define('APP_NAME', 'Rustnite');
define('APP_URL', 'http://localhost');
define('PAYPAL_DONATE_URL', 'https://www.paypal.com/donate/?hosted_button_id=RKBHLNTG326DA');

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600 * 24 * 30); // 30 days

// XP and leveling system
define('XP_PER_LESSON', 100);
define('XP_PER_LEVEL', 1000);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>