<?php
session_start();
include '../config/config.php';
// require_once __DIR__ . '/../vendor/autoload.php';

// use Dotenv\Dotenv;

// $dotenv = Dotenv::createImmutable(__DIR__ . '/..'); 
// $dotenv->load();

// // Sử dụng biến từ .env
// echo "App name: " . $_ENV['APP_NAME'];

// Xử lý reCAPTCHA nếu có
if (isset($_POST['g-recaptcha-response'])) {
    $recapcha_post = $_POST['g-recaptcha-response'];
    if (!$recapcha_post) {
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    } else {
        $secret = $_ENV['RECAPTCHA_SECRET'];
        if (!$secret) {
            header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
            exit();
        }
        // Verify reCAPTCHA
        $verifyResponse = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response={$recapcha_post}"
        );
        $responseData = json_decode($verifyResponse);
        if (!$responseData->success) {
            header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
            exit();
        }
    }
}

// Nếu người dùng gửi comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    $parent_id = $_POST['parent_id'] ?? NULL;

    // Giới hạn thời gian: không quá 5 bình luận trong 60 giây
    $time_limit = 60;
    $query_count_cmt = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND created_at > NOW() - INTERVAL ? SECOND");
    $query_count_cmt->bind_param("iis", $user_id, $post_id, $time_limit);
    $query_count_cmt->execute();
    $comment_count = $query_count_cmt->get_result()->fetch_row()[0];

    if ($comment_count >= 5) {
        header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_1");
        exit();
    }

    // Giới hạn bình luận trùng lặp nội dung
    $query_count_cmt_same = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND content LIKE ?");
    $content_same = "%" . $content . "%";
    $query_count_cmt_same->bind_param("sss", $user_id, $post_id, $content_same);
    $query_count_cmt_same->execute();
    $comment_count_same = $query_count_cmt_same->get_result()->fetch_row()[0];

    if ($comment_count_same >= 5) {
        header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_2");
        exit();
    }

    // Thêm bình luận vào CSDL
    $query = $conn->prepare("INSERT INTO comment (id, content, user_id, post_id, parent_id) VALUES (UUID(), ?, ?, ?, ?)");
    $query->bind_param("ssss", $content, $user_id, $post_id, $parent_id);
    $query->execute();
}

// Redirect về trang chi tiết bài viết
header("Location: /Comment-Nhom5/posts/{$post_id}");
exit();
