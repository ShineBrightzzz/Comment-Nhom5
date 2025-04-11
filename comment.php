<?php
session_start();
include 'config.php';
//lấy recapcha
if(isset($_POST['g-recaptcha-response'])){
$recapcha_post = $_POST['g-recaptcha-response'] ?? null;
if (!$recapcha_post) {
   header("Location: index.php?error=recaptcha");
   exit();
}else
{
    $secret = '6LevCQsrAAAAADT1MId90bAQablX6idWMfYdarlE';
    //get verify response data
    $verifyResponse = file_get_contents( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response'] );
    $responseData   = json_decode( $verifyResponse );
    if ( $responseData->success ){
        //recaptcha success
    } else{
        //recaptcha failed
        header("Location: index.php?error=recaptcha");
        exit();
    }
}
}

// Lấy bài viết
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    $parent_id = $_POST['parent_id'] ?? NULL;

    // Kiểm tra số bình luận của người dùng trong khoảng thời gian đã cho
    // Giới hạn thời gian
    $time_limit = 60; // 1 phút
    $query_count_cmt = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND created_at > NOW() - INTERVAL ? SECOND");
    $query_count_cmt->bind_param("iis", $user_id, $post_id, $time_limit);
    $query_count_cmt->execute();
    $result = $query_count_cmt->get_result()->fetch_assoc();
    $comment_count = $result['COUNT(*)'];
    //kiểm tra số bình luận
    if ($comment_count >= 5) {
        header("Location: index.php?error=limit_1");
        exit();
    }
    //Kiểm tra số lượng bình luận giống nhau của cùng một user_id
    $query_count_cmt_same = $conn->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND post_id = ? AND content LIKE ?");
    $content_same = "%" . $content . "%";
    $query_count_cmt_same->bind_param("sss", $user_id, $post_id, $content_same);
    // Thực thi câu lệnh SQL
    $query_count_cmt_same->execute();
    $result_same = $query_count_cmt_same->get_result()->fetch_assoc();
    $comment_count_same = $result_same['COUNT(*)'];
    //kiểm tra số bình luận giống nhau
    if ($comment_count_same >= 5) {
        header("Location: index.php?error=limit_2");
        exit();
    }

    
    //insert bình luận vào database
    $query = $conn->prepare("INSERT INTO comment (id, content, user_id, post_id, parent_id) VALUES (UUID(), ?, ?, ?, ?)");
    $query->bind_param("ssss", $content, $user_id, $post_id, $parent_id);
    $query->execute();
}

header("Location: index.php?id=" . $post_id);
exit();
?>