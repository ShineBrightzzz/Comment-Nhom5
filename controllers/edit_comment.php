<?php
session_start();
include '../config/config.php';

// Kiểm tra xem có phải là phương thức POST không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);

    // Kiểm tra quyền sở hữu bình luận (người dùng phải là chủ của bình luận)
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    // Kiểm tra người dùng có quyền chỉnh sửa bình luận này không
    if ($comment && $_SESSION['user_id'] == $comment['user_id']) {
        // Cập nhật nội dung bình luận
        $stmt = $conn->prepare("UPDATE comment SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $content, $comment_id);
        $stmt->execute();
    } else {
        // Nếu không phải chủ của bình luận
        echo "<div class='alert alert-danger'>Bạn không có quyền sửa bình luận này.</div>";
        exit;
    }

    // Quay lại trang bài viết
    header("Location: ../pages/posts.php?posts=" . $post_id);
    exit;
} else {
    // Nếu không phải phương thức POST
    echo "<div class='alert alert-danger'>Phương thức không hợp lệ.</div>";
}
?>
