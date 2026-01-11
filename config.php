<?php
// Database configuration
define('DB_HOST', 'db.fr-pari1.bengt.wasmernet.com');
define('DB_NAME', 'rustnite');
define('DB_USER', '3ac2b01570198000841f60ead8ad');
define('DB_PASS', '06963ac2-b015-725b-8000-620e12cfcb0e');
define('DB_PORT', '10272');

// App configuration
define('APP_NAME', 'Rustnite');
define('APP_URL', 'https://rustnite.wasmer.app');
define('PAYPAL_DONATE_URL', 'https://www.paypal.com/donate/?hosted_button_id=RKBHLNTG326DA');

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600 * 24 * 30); // 30 days

// XP and leveling system
define('XP_PER_LESSON', 100);
define('XP_PER_LEVEL', 1000);

// Error reporting (enable for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>