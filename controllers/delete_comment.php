<?php
session_start();
include '../config/config.php';

if (isset($_GET['id']) && isset($_GET['post_id'])) {
    $comment_id = $_GET['id'];
    $post_id = $_GET['post_id'];

    // Kiểm tra quyền xóa
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && $_SESSION['user_id'] == $comment['user_id']) {
        $stmt = $conn->prepare("DELETE FROM comment WHERE id = ? OR parent_id = ?");
        $stmt->bind_param("ss", $comment_id, $comment_id); // Xóa luôn các comment con
        $stmt->execute();
    }

    header("Location: index1.php?id=" . $post_id);
}
?>
