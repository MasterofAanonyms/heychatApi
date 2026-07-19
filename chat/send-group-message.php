<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$grpId   = trim($data['grpId'] ?? '');
$sender  = trim($data['sender'] ?? '');
$message = trim($data['message'] ?? '');

if (empty($grpId) || empty($sender) || empty($message)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Group id, sender, and message are required"]);
    exit;
}

try {
    $stmt = $con->prepare("INSERT INTO grp_msg (time, msg, grp_id, sender_mobile) VALUES (NOW(), ?, ?, ?)");
    $stmt->execute([$message, $grpId, $sender]);
    $msgId = $con->lastInsertId();

    echo json_encode([
        "status" => "success",
        "message_data" => [
            "id"      => $msgId,
            "message" => $message,
            "sent_at" => date("Y-m-d H:i:s"),
            "sender"  => $sender,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}