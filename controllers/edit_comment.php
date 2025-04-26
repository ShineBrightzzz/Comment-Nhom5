<?php
session_start();
include '../config/config.php';
include '../utils/formatTime.php';

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $content = trim($_POST['content']);
    $post_id = $_POST['post_id'] ?? NULL;

    // Kiểm tra quyền
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']) {
        // Nếu không có post_id từ form, lấy post_id từ comment
        if ($post_id === NULL) {
            $post_id = $comment['post_id'];
        }
        
        // Giữ nguyên parent_id khi cập nhật
        $stmt = $conn->prepare("UPDATE comment SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ss", $content, $comment_id);
        
        if ($stmt->execute()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'comment_id' => $comment_id,
                    'content' => nl2br(htmlspecialchars($content)),
                    'updated_at' => timeAgo(date('Y-m-d H:i:s'))
                ]);
                exit();
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Không thể cập nhật bình luận'
                ]);
                exit();
            }
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Không có quyền sửa bình luận này'
            ]);
            exit();
        }
    }

    if (!$isAjax) {
        header("Location: /Comment-Nhom5/posts/{$post_id}");
        exit();
    }
}

if (!$isAjax) {
    header("Location: ../index.php");
}
?>
