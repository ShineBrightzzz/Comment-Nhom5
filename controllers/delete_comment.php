<?php
session_start();
include '../config/config.php';
$post_id = $_POST['posts'] ?? NULL;

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Handling both GET and POST requests for backward compatibility
if (isset($_POST['comment_id']) || (isset($_GET['id']) && isset($_GET['post_id']))) {
    // Get comment ID from either POST or GET
    $comment_id = $_POST['comment_id'] ?? $_GET['id'];
    $post_id = NULL; // Will be fetched from the comment
    
    if (isset($_GET['post_id'])) {
        $post_id = $_GET['post_id'];
    }

    // Kiểm tra quyền xóa
    $stmt = $conn->prepare("SELECT * FROM comment WHERE id = ?");
    $stmt->bind_param("s", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();
    $success = false;
    $message = "Không có quyền xóa bình luận này";

    if ($comment && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']) {
        // Get the post ID if not provided
        if ($post_id === NULL) {
            $post_id = $comment['post_id'];
        }
        
        // Function to recursively delete comments and their children
        function deleteCommentRecursive($conn, $commentId) {
            // Find all child comments
            $stmt = $conn->prepare("SELECT id FROM comment WHERE parent_id = ?");
            $stmt->bind_param("s", $commentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Recursively delete all children first
            while ($row = $result->fetch_assoc()) {
                deleteCommentRecursive($conn, $row['id']);
            }
            
            // Then delete the comment itself
            $deleteStmt = $conn->prepare("DELETE FROM comment WHERE id = ?");
            $deleteStmt->bind_param("s", $commentId);
            $deleteStmt->execute();
        }
        
        // Start the recursive deletion
        try {
            deleteCommentRecursive($conn, $comment_id);
            $success = true;
            $message = "Đã xóa bình luận và tất cả phản hồi";
        } catch (Exception $e) {
            $message = "Lỗi khi xóa bình luận: " . $e->getMessage();
        }
    }
    
    // Return JSON response for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'comment_id' => $comment_id,
            'post_id' => $post_id
        ]);
        exit;
    }

    // For non-AJAX requests, redirect back
    if ($post_id) {
        header("Location: /Comment-Nhom5/posts/{$post_id}");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

// If no valid parameters, redirect to home
header("Location: ../index.php");
?>
