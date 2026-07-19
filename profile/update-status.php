<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$mobile   = trim($data['mobile'] ?? '');
$statusId = $data['status_id'] ?? null;

if (empty($mobile) || !in_array($statusId, [1, 2], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Mobile and a valid status_id (1 or 2) are required"]);
    exit;
}

try {
    $check = $con->prepare("SELECT mobile FROM users WHERE mobile = ?");
    $check->execute([$mobile]);

    if ($check->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    $stmt = $con->prepare("UPDATE users SET status_id = ? WHERE mobile = ?");
    $stmt->execute([$statusId, $mobile]);

    echo json_encode(["status" => "success", "status_id" => $statusId]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}