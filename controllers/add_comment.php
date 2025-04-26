<?php
session_start();
include '../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    $parent_id = $_POST['parent_id'] ?? NULL;

    // Check spam time
    $time_limit = 60;
    $query_count_cmt = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND created_at > NOW() - INTERVAL ? SECOND");
    $query_count_cmt->bind_param("iis", $user_id, $post_id, $time_limit);
    $query_count_cmt->execute();
    $comment_count = $query_count_cmt->get_result()->fetch_row()[0];

    if ($comment_count >= 5) {
        if (!isCaptchaVerified()) {
            if (empty($_POST['g-recaptcha-response'])) {
                header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_1");
                exit();
            }
            validateCaptcha($_POST['g-recaptcha-response']);
        }
    }

    // Check spam nội dung
    $query_count_cmt_same = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND content LIKE ?");
    $content_same = "%" . $content . "%";
    $query_count_cmt_same->bind_param("sss", $user_id, $post_id, $content_same);
    $query_count_cmt_same->execute();
    $comment_count_same = $query_count_cmt_same->get_result()->fetch_row()[0];

    if ($comment_count_same >= 5) {
        if (!isCaptchaVerified()) {
            if (empty($_POST['g-recaptcha-response'])) {
                header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_2");
                exit();
            }
            validateCaptcha($_POST['g-recaptcha-response']);
        }
    }

    // Insert bình luận
    $query = $conn->prepare("INSERT INTO comment (id, content, user_id, post_id, parent_id) VALUES (UUID(), ?, ?, ?, ?)");
    $query->bind_param("ssss", $content, $user_id, $post_id, $parent_id);
    $query->execute();
}

// Hàm kiểm tra session captcha còn hiệu lực không
function isCaptchaVerified()
{
    return isset($_SESSION['captcha_verified_until']) && $_SESSION['captcha_verified_until'] > time();
}

// Hàm xác minh captcha
function validateCaptcha($recaptcha_response)
{
    $secret = $_ENV['RECAPTCHA_SECRET'];
    if (!$secret) {
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    }
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$recaptcha_response");
    $responseData = json_decode($verifyResponse);
    if (!$responseData->success) {
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    }
    
    //  lưu session trong 10 phút
    $_SESSION['captcha_verified_until'] = time() + 600; 
}

header("Location: /Comment-Nhom5/posts/{$post_id}");
exit();
?>
