<?php
header("Content-Type: application/json");
require "../config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$fname = trim($data['firstName'] ??'');
$lname = trim($data['lastName'] ??'');
$mobile = trim($data['phone'] ??'');
$password = $data['password'] ??'';

if (empty($fname) || empty($lname) || empty($mobile) || empty($password)) {

    http_response_code(400);

    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

if (strlen($password) < 6 || $password > 10) {

    http_response_code(400);

    echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters and maximum 10 characters."]);
    exit;
}

try {

    $check = $con->prepare("SELECT mobile FROM users WHERE mobile = ?");
    $check->execute([$mobile]);

    if ($check->rowCount() > 0) {

        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Mobile number already registered"]);
        exit;

    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $con->prepare(

        "INSERT INTO users (mobile, fname, lname, pw, status_id) VALUES (?, ?, ?, ?, ?)"

    );

    $stmt->execute([$mobile, $fname, $lname, $hashedPassword, 1]);

    echo json_encode(["status" => "success", "message" => "User registered successfully"]);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    
}
