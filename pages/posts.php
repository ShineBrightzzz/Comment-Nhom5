<?php
include '../config/config.php';
include '../layouts/header.php';
include '../utils/formatTime.php';
include '../services/users.php';
include '../services/comments.php';


$error = $_GET['error'] ?? null;
$post_id = $_GET['posts'] ?? null;

$query = $conn->prepare("SELECT * FROM post WHERE id = ?");
$query->bind_param("s", $post_id);
$query->execute();
$post = $query->get_result()->fetch_assoc();
?>

<div class="container my-5">    
<?php if (!empty($post) && is_array($post)): ?>
    <div class="card mb-4 border-0 shadow">
        <div class="card-body">
            <h1 class="card-title"><?php echo htmlspecialchars($post['title'] ?? 'Không có tiêu đề'); ?></h1>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'] ?? 'Không có nội dung')); ?></p>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="mb-4"><i class="bi bi-chat-left-text me-2"></i>Bình luận</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="commentForm" method="post" action="../controllers/add_comment.php" class="comment-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <div class="mb-3">
                        <div class="position-relative">
                            <textarea name="content" class="form-control rounded pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết bình luận..." rows="1"></textarea>
                            <button type="submit" class="btn position-absolute top-0 end-0 mt-1 me-2" style="background: none; border: none; color: #0d6efd;">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                    <div id="comment-error-container" class="alert alert-warning d-none"></div>
                    <?php if ($error == 'limit_1'): ?>
                        <div class="alert alert-warning">Bạn đã bình luận quá nhiều trong khoảng thời gian ngắn. Vui lòng xác nhận.</div>
                        <div class="g-recaptcha mb-2" data-sitekey="6Ld_mCYrAAAAAKF2djMSAAiZBcEqTxCEE8U-8GX8" data-callback="enableSubmit"></div>
                    <?php elseif ($error == 'limit_2'): ?>
                        <div class="alert alert-warning">Bạn đã bình luận quá giống nhau. Vui lòng xác nhận.</div>
                        <div class="g-recaptcha mb-2" data-sitekey="6Ld_mCYrAAAAAKF2djMSAAiZBcEqTxCEE8U-8GX8" data-callback="enableSubmit"></div>
                    <?php elseif ($error == 'recaptcha'): ?>
                        <div class="alert alert-danger">Vui lòng xác minh bạn là con người.</div>
                        <div class="g-recaptcha mb-2" data-sitekey="6Ld_mCYrAAAAAKF2djMSAAiZBcEqTxCEE8U-8GX8" data-callback="enableSubmit"></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i> Vui lòng <a href="/Comment-Nhom5/login" class="alert-link">đăng nhập</a> để bình luận.
            </div>
        <?php endif; ?>
    </div>

    <div class="comments-section">
        <h4 class="mb-3"><i class="bi bi-chat-square-text me-2"></i>Danh sách bình luận</h4>
        <?php displayComments($conn, $post_id); ?>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> Bài viết không tồn tại.
    </div>
<?php endif; ?>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Comment Functionality -->
<script src="/Comment-Nhom5/utils/comments.js"></script>

<script src="../utils/formatTime.js"></script>

<script>
function enableSubmit(token) {
    // After reCAPTCHA verification, submit the form
    document.getElementById('commentForm').submit();
}
</script>