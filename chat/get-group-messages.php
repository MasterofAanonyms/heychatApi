<?php
header("Content-Type: application/json");
require "../config/connection.php";

$grpId = trim($_GET['grpId'] ?? '');

if (empty($grpId)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Group id is required"]);
    exit;
}

try {
    $stmt = $con->prepare("
        SELECT gm.id, gm.time, gm.msg, gm.media, gm.sender_mobile,
               u.fname AS sender_fname, u.lname AS sender_lname
        FROM grp_msg gm
        LEFT JOIN users u ON u.mobile = gm.sender_mobile
        WHERE gm.grp_id = ?
        ORDER BY gm.time DESC
    ");
    $stmt->execute([$grpId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function ($m) {
        return [
            "id"          => $m['id'],
            "message"     => $m['msg'],
            "sent_at"     => $m['time'],
            "sender"      => $m['sender_mobile'],
            "sender_name" => trim(($m['sender_fname'] ?? '') . ' ' . ($m['sender_lname'] ?? '')),
        ];
    }, $messages);

    echo json_encode(["status" => "success", "messages" => $result]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}