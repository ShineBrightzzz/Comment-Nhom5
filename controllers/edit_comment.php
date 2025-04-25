<?php
session_start();
include '../config/config.php';
$post_id = $_POST['posts'] ?? NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $content = trim($_POST['content']);

    // Kiểm tra quyền
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && $_SESSION['user_id'] == $comment['user_id']) {
        // Nếu không có post_id từ form, lấy post_id từ comment
        if ($post_id === NULL) {
            $post_id = $comment['post_id'];
        }
        
        // Giữ nguyên parent_id khi cập nhật
        $stmt = $conn->prepare("UPDATE comment SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ss", $content, $comment_id);
        $stmt->execute();
    }

    header("Location: /Comment-Nhom5/posts/{$post_id}");
    exit;
}

header("Location: ../index.php");
?>
