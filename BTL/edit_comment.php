<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $content = trim($_POST['content']);

    // Kiểm tra quyền
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && $_SESSION['user_id'] == $comment['user_id']) {
        $stmt = $conn->prepare("UPDATE comment SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ss", $content, $comment_id);
        $stmt->execute();
    }

    header("Location: index.php?id=" . $comment['post_id']);
}
?>
