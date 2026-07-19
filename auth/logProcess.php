<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$mobile   = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';

if (empty($mobile) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Mobile number and password are required"]);
    exit;
}

try {
    $stmt = $con->prepare("SELECT mobile, fname, lname, pw, status_id FROM users WHERE mobile = ?");
    $stmt->execute([$mobile]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid mobile number or password"]);
        exit;
    }

    if (!password_verify($password, $user['pw'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid mobile number or password"]);
        exit;
    }

    // Never send the password hash back to the client
    unset($user['pw']);

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => $user
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}