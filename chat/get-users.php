<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$host = 'localhost';
$db = 'heychat';
$user = 'root';
$password = '';

try {
    $conn = new mysqli($host, $user, $password, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get all users except the current user
    $current_mobile = isset($_GET['mobile']) ? $conn->real_escape_string($_GET['mobile']) : '';

    if (empty($current_mobile)) {
        echo json_encode(['success' => false, 'msg' => 'Current user mobile is required']);
        exit;
    }

    $query = "SELECT mobile, fname, lname, img_url FROM users WHERE mobile != '$current_mobile' ORDER BY fname ASC";
    
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'mobile' => $row['mobile'],
            'fname' => $row['fname'],
            'lname' => $row['lname'],
            'img_url' => $row['img_url']
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
