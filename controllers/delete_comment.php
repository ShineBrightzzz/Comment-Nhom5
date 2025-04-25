<?php
session_start();
include '../config/config.php';

if (isset($_GET['comment_id'])) {
    $comment_id = $_GET['comment_id'];

    // Lấy thông tin bình luận
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();

    // Kiểm tra tồn tại và quyền
    if ($comment && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']) {
        // Xóa cả bình luận chính và phản hồi của nó
        $deleteStmt = $conn->prepare("DELETE FROM comment WHERE id = ? OR parent_id = ?");
        $deleteStmt->bind_param("ss", $comment_id, $comment_id);
        $deleteStmt->execute();

        // Tránh lỗi nếu không có dòng nào bị xóa
        if ($deleteStmt->affected_rows === 0) {
            die("Không thể xóa bình luận: không có dòng nào bị ảnh hưởng.");
        }

        header("Location: ../pages/posts.php?posts=" . $comment['post_id']);
        exit;
    } else {
        die("Không có quyền xóa hoặc bình luận không tồn tại.");
    }
} else {
    die("Thiếu comment_id");
}