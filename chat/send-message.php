<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$sender   = trim($data['sender'] ?? '');
$receiver = trim($data['receiver'] ?? '');
$message  = trim($data['message'] ?? '');

if (empty($sender) || empty($receiver) || empty($message)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Sender, receiver, and message are required"]);
    exit;
}

try {
    $con->beginTransaction();

    $stmt = $con->prepare("INSERT INTO dual_chat (time, msg, sender) VALUES (NOW(), ?, ?)");
    $stmt->execute([$message, $sender]);
    $chatId = $con->lastInsertId();

    $stmt2 = $con->prepare("INSERT INTO dual_chat_has_users (chat_chat_id, reciever) VALUES (?, ?)");
    $stmt2->execute([$chatId, $receiver]);

    $con->commit();

    echo json_encode([
        "status" => "success",
        "message_data" => [
            "id"      => $chatId,
            "message" => $message,
            "sent_at" => date("Y-m-d H:i:s"),
            "sender"  => $sender,
        ],
    ]);

} catch (PDOException $e) {
    $con->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}