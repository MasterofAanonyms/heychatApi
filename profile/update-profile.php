<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$mobile = trim($data['mobile'] ?? '');
$fname  = trim($data['fname'] ?? '');
$lname  = trim($data['lname'] ?? '');
$about  = trim($data['about'] ?? '');

if (empty($mobile) || empty($fname) || empty($lname)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "First name, last name, and mobile are required"]);
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

    // Note: img_url is intentionally NOT updated here
    $stmt = $con->prepare("
        UPDATE users
        SET fname = ?, lname = ?, about = ?
        WHERE mobile = ?
    ");
    $stmt->execute([$fname, $lname, $about, $mobile]);

    // Return the fresh user row so the frontend can sync AsyncStorage
    $userStmt = $con->prepare("SELECT fname, lname, mobile, about, img_url, status_id FROM users WHERE mobile = ?");
    $userStmt->execute([$mobile]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "message" => "Profile updated successfully", "user" => $user]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}