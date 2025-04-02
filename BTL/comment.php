<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    $parent_id = $_POST['parent_id'] ?? NULL;

    $query = $conn->prepare("INSERT INTO comment (id, content, user_id, post_id, parent_id) VALUES (UUID(), ?, ?, ?, ?)");
    $query->bind_param("ssss", $content, $user_id, $post_id, $parent_id);
    $query->execute();
}

header("Location: index.php?id=" . $post_id);
exit();
?>