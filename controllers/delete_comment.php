<?php
session_start();
include '../config/config.php';
$post_id = $_POST['posts'] ?? NULL;
// Handling both GET and POST requests for backward compatibility
if (isset($_POST['comment_id']) || (isset($_GET['id']) && isset($_GET['post_id']))) {
    // Get comment ID from either POST or GET
    $comment_id = $_POST['comment_id'] ?? $_GET['id'];
    $post_id = NULL; 
    
    if (isset($_GET['post_id'])) {
        $post_id = $_GET['post_id'];
    }

    // Kiểm tra quyền xóa
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']) {
        // Get the post ID if not provided
        if ($post_id === NULL) {
            $post_id = $comment['post_id'];
        }
        
        // Hàm xóa comment theo cách đệ quy
        function deleteCommentRecursively($conn, $comment_id) {
            // Tìm tất cả các comment con
            $stmt = $conn->prepare("SELECT id FROM comment WHERE parent_id = ?");
            $stmt->bind_param("s", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Xóa các comment con trước (đệ quy)
            while ($child = $result->fetch_assoc()) {
                deleteCommentRecursively($conn, $child['id']);
            }
            
            // Sau khi đã xóa hết các comment con, xóa comment hiện tại
            $delete_stmt = $conn->prepare("DELETE FROM comment WHERE id = ?");
            $delete_stmt->bind_param("s", $comment_id);
            $delete_stmt->execute();
        }
        
        // Gọi hàm xóa comment đệ quy
        deleteCommentRecursively($conn, $comment_id);
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => 'Đã xóa bình luận']);
            exit;
        }
    }

    header("Location: /Comment-Nhom5/posts/{$post_id}");
    exit;
}

// If no valid parameters, redirect to home
header("Location: ../index.php");
?>
