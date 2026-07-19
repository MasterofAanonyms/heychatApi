<?php
header("Content-Type: application/json");
require "../config/connection.php";

$mobile = trim($_POST['mobile'] ?? '');

if (empty($mobile)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Mobile number is required"]);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No image uploaded or upload error"]);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$fileType = mime_content_type($_FILES['image']['tmp_name']);

if (!in_array($fileType, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Only JPG, PNG, and WEBP images are allowed"]);
    exit;
}

$maxSizeBytes = 5 * 1024 * 1024; // 5 MB
if ($_FILES['image']['size'] > $maxSizeBytes) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Image must be under 5MB"]);
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

    // Build a unique filename so different users' uploads never collide
    $extension = match ($fileType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    };
    $fileName = $mobile . '_' . time() . '.' . $extension;
    $targetPath = __DIR__ . '/uploads/' . $fileName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to save image on server"]);
        exit;
    }

    // Store a relative URL path — NOT a filesystem path — so the frontend can load it directly
    $imgUrl = '/profile/uploads/' . $fileName;

    $stmt = $con->prepare("UPDATE users SET img_url = ? WHERE mobile = ?");
    $stmt->execute([$imgUrl, $mobile]);

    echo json_encode(["status" => "success", "img_url" => $imgUrl]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}