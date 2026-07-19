<?php
header("Content-Type: application/json");
require "../config/connection.php";

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

// Validate input
if (empty($data['group_name'])) {
    http_response_code(400);
    echo json_encode(["msg" => "Group name is required"]);
    exit;
}

if (empty($data['creator_mobile'])) {
    http_response_code(400);
    echo json_encode(["msg" => "Creator mobile is required"]);
    exit;
}

if (empty($data['user_mobiles']) || !is_array($data['user_mobiles'])) {
    http_response_code(400);
    echo json_encode(["msg" => "User list is required"]);
    exit;
}

$group_name = trim($data['group_name']);
$creator_mobile = trim($data['creator_mobile']);
$user_mobiles = $data['user_mobiles'];

try {
    // Start transaction
    $con->beginTransaction();

    // 1. Create group with creator as create_by
    $insertGrpStmt = $con->prepare("
        INSERT INTO grp (name, create_by)
        VALUES (?, ?)
    ");
    $insertGrpStmt->execute([$group_name, $creator_mobile]);
    $group_id = $con->lastInsertId();

    // 2. Add all users to the group (including the creator)
    $insertUsersStmt = $con->prepare("
        INSERT INTO users_has_grp (users_mobile, grp_id)
        VALUES (?, ?)
    ");

    // Add all selected users
    foreach ($user_mobiles as $mobile) {
        $insertUsersStmt->execute([trim($mobile), $group_id]);
    }

    // Also add the creator if not already in the list
    if (!in_array($creator_mobile, $user_mobiles)) {
        $insertUsersStmt->execute([$creator_mobile, $group_id]);
    }

    // Commit transaction
    $con->commit();

    http_response_code(201);
    echo json_encode([
        "msg" => "Group created successfully",
        "group_id" => $group_id,
        "group_name" => $group_name
    ]);

} catch (PDOException $e) {
    // Rollback on error
    $con->rollBack();
    http_response_code(500);
    echo json_encode(["msg" => "Server error: " . $e->getMessage()]);
}
?>
