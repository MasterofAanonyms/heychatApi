<?php
$host = "localhost";
$dbname = "beta_x5";
$username = "root";
$password = "Pa$$@word12"; 

try {

    $con = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    echo json_encode(["status" => "error", "message" => "DB connection failed: " . $e->getMessage()]);
    exit;
    
}