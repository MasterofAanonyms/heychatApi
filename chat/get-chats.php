<?php
header("Content-Type: application/json");
require "../config/connection.php";

$mobile = trim($_GET['mobile'] ?? '');

if (empty($mobile)) {
    http_response_code(400);
    echo json_encode(["msg" => "Mobile number is required"]);
    exit;
}

try {
    $result = [];

    // ---------- DIRECT (1-on-1) CHATS ----------
    $stmt = $con->prepare("
        SELECT dc.id, dc.time, dc.msg, dc.media, dc.sender, dhu.reciever
        FROM dual_chat dc
        JOIN dual_chat_has_users dhu ON dhu.chat_chat_id = dc.id
        WHERE dc.sender = ? OR dhu.reciever = ?
        ORDER BY dc.time DESC
    ");
    $stmt->execute([$mobile, $mobile]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $latestByPartner = [];
    foreach ($messages as $msg) {
        $otherMobile = ($msg['sender'] === $mobile) ? $msg['reciever'] : $msg['sender'];
        if (!isset($latestByPartner[$otherMobile])) {
            $latestByPartner[$otherMobile] = $msg;
        }
    }

    if (!empty($latestByPartner)) {
        $partnerMobiles = array_keys($latestByPartner);
        $placeholders = implode(',', array_fill(0, count($partnerMobiles), '?'));

        $userStmt = $con->prepare("
            SELECT mobile, fname, lname, img_url, status_id
            FROM users
            WHERE mobile IN ($placeholders)
        ");
        $userStmt->execute($partnerMobiles);
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        $usersByMobile = [];
        foreach ($users as $u) {
            $usersByMobile[$u['mobile']] = $u;
        }

        foreach ($latestByPartner as $otherMobile => $msg) {
            if (!isset($usersByMobile[$otherMobile])) continue;

            $result[] = [
                "type" => "direct",
                "user" => [
                    "fname"     => $usersByMobile[$otherMobile]['fname'],
                    "lname"     => $usersByMobile[$otherMobile]['lname'],
                    "mobile"    => $usersByMobile[$otherMobile]['mobile'],
                    "img"       => $usersByMobile[$otherMobile]['img_url'],
                    "status_id" => $usersByMobile[$otherMobile]['status_id'],
                ],
                "last_message" => [
                    "message"      => $msg['msg'],
                    "sent_at"      => $msg['time'],
                    "chat_chat_id" => $msg['id'],
                ],
            ];
        }
    }

    // ---------- GROUP CHATS ----------
    $grpStmt = $con->prepare("
        SELECT gm.id, gm.time, gm.msg, gm.media, gm.sender_mobile, gm.grp_id, g.name,
               u.fname AS sender_fname
        FROM grp_msg gm
        JOIN grp g ON g.id = gm.grp_id
        JOIN users_has_grp uhg ON uhg.grp_id = gm.grp_id
        LEFT JOIN users u ON u.mobile = gm.sender_mobile
        WHERE uhg.users_mobile = ?
        ORDER BY gm.time DESC
    ");
    $grpStmt->execute([$mobile]);
    $grpMessages = $grpStmt->fetchAll(PDO::FETCH_ASSOC);

    $latestByGroup = [];
    foreach ($grpMessages as $msg) {
        if (!isset($latestByGroup[$msg['grp_id']])) {
            $latestByGroup[$msg['grp_id']] = $msg;
        }
    }

    // Also get groups without any messages yet
    $grpNoMsgStmt = $con->prepare("
        SELECT g.id, g.name, g.create_by
        FROM grp g
        JOIN users_has_grp uhg ON uhg.grp_id = g.id
        WHERE uhg.users_mobile = ? AND g.id NOT IN (
            SELECT DISTINCT grp_id FROM grp_msg
        )
        ORDER BY g.id DESC
    ");
    $grpNoMsgStmt->execute([$mobile]);
    $groupsNoMsg = $grpNoMsgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add groups without messages to the result
    foreach ($groupsNoMsg as $grp) {
        $latestByGroup[$grp['id']] = [
            'id' => null,
            'grp_id' => $grp['id'],
            'name' => $grp['name'],
            'msg' => '',
            'time' => date('Y-m-d H:i:s'), // Current time for new groups
            'sender_mobile' => null,
            'sender_fname' => null,
        ];
    }

    foreach ($latestByGroup as $grpId => $msg) {
        // Show "You" if the logged-in user sent the last message, otherwise their first name
        $senderLabel = ($msg['sender_mobile'] === $mobile)
            ? "You"
            : ($msg['sender_fname'] ?? "Group");

        $result[] = [
            "type" => "group",
            "group" => [
                "id"   => $grpId,
                "name" => $msg['name'],
            ],
            "last_message" => [
                "message"      => $msg['msg'] ?? "No messages yet",
                "sender"       => $senderLabel,
                "sent_at"      => $msg['time'],
                "chat_chat_id" => $msg['id'],
            ],
        ];
    }

    // ---------- MERGE + SORT BY MOST RECENT ----------
    usort($result, fn($a, $b) =>
        strtotime($b['last_message']['sent_at']) - strtotime($a['last_message']['sent_at'])
    );

    echo json_encode($result);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["msg" => "Server error: " . $e->getMessage()]);
}
?>
