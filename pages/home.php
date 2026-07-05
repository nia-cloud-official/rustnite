<?php
// Home page is now handled by index.php redirect logic
// This file is kept as a fallback but shouldn't normally be reached
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?page=login");
    exit();
}
header("Location: index.php?page=dashboard");
exit();
