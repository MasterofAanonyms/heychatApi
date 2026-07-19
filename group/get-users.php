<?php
header("Content-Type: application/json");
require "../config/connection.php";

$currentUser = $_GET["mobile"] ?? "";

if (empty($currentUser)) {
    http_response_code(400);
    echo json_encode(["msg" => "Mobile number is required"]);
    exit;
}

try {
    $stmt = $con->prepare("
        SELECT mobile, fname, lname, img_url
        FROM users
        WHERE mobile <> ?
        ORDER BY fname ASC
    ");

    $stmt->execute([$currentUser]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["msg" => "Server error: " . $e->getMessage()]);
}
?>
