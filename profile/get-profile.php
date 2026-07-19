<?php
header("Content-Type: application/json");
require "../config/connection.php";

$mobile = trim($_GET['mobile'] ?? '');

if (empty($mobile)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Mobile number is required"]);
    exit;
}

try {
    $stmt = $con->prepare("
        SELECT fname, lname, mobile, about, img_url
        FROM users
        WHERE mobile = ?
    ");
    $stmt->execute([$mobile]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    echo json_encode(["status" => "success", "user" => $user]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}