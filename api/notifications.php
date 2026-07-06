<?php
session_start();
require_once "../config.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in", "notifications" => []]);
    exit();
}

$user_id = $_SESSION["user_id"];

// Handle mark as read
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input["mark_read"])) {
        mark_notification_read((int) $input["mark_read"], $user_id);
        echo json_encode(["success" => true]);
        exit();
    }
    if (isset($input["mark_all_read"])) {
        $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$user_id]);
        echo json_encode(["success" => true]);
        exit();
    }
}

// Get notifications
$notifications = get_user_notifications($user_id, 20);
$unread_count = get_unread_notification_count($user_id);

echo json_encode([
    "success" => true,
    "notifications" => $notifications,
    "unread_count" => $unread_count,
]);
