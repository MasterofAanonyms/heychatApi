<?php
header("Content-Type: application/json");
require "../config/connection.php";

$mobile      = trim($_GET['mobile'] ?? '');
$otherMobile = trim($_GET['otherMobile'] ?? '');

if (empty($mobile) || empty($otherMobile)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Both mobile numbers are required"]);
    exit;
}

try {
    $stmt = $con->prepare("
        SELECT dc.id, dc.time, dc.msg, dc.media, dc.sender, dhu.reciever
        FROM dual_chat dc
        JOIN dual_chat_has_users dhu ON dhu.chat_chat_id = dc.id
        WHERE (dc.sender = ? AND dhu.reciever = ?)
           OR (dc.sender = ? AND dhu.reciever = ?)
        ORDER BY dc.time DESC
    ");
    $stmt->execute([$mobile, $otherMobile, $otherMobile, $mobile]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function ($m) {
        return [
            "id"      => $m['id'],
            "message" => $m['msg'],
            "sent_at" => $m['time'],
            "sender"  => $m['sender'],
        ];
    }, $messages);

    echo json_encode(["status" => "success", "messages" => $result]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}