<?php

// require_once __DIR__ . '/../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); 
// $dotenv->load();

// Lấy thông tin từ .env
$servername = "localhost";
$username = 'root';
$password = '';
$dbname = "comment_db";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>