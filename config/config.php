<?php
$servername = "localhost";
$username = "root";  
$password = "";       
$dbname = "comment_db";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>