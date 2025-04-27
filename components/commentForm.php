<?php
function renderCommentForm($post_id, $error = null) {
    ob_start(); ?>
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    <form method="post" action="comment.php" class="comment-form">
        <input type="hidden" name="post_id" value="<?= $post_id ?>">
        <textarea name="content" required placeholder="Viết bình luận..."></textarea>
        <?php
        if ($_GET['error'] == 'limit_1') {
            echo "<p class='error'>Bạn đã bình luận quá nhiều trong thời gian ngắn. Vui lòng xác nhận.</p>";
            echo "<div class='g-recaptcha' data-sitekey='6LdllSYrAAAAAOKNinLeWIofE-_v1k-S17V_Kvi9'></div>";
        } elseif ($_GET['error'] == 'limit_2') {
            echo "<p class='error'>Bạn đã bình luận quá giống nhau. Vui lòng xác nhận.</p>";
            echo "<div class='g-recaptcha' data-sitekey='6LdllSYrAAAAAOKNinLeWIofE-_v1k-S17V_Kvi9'></div>";
        } elseif ($error === 'recaptcha') {
            echo "<p class='error'>Vui lòng xác minh bạn là con người.</p>";
        }
        ?>
        <button type="submit">Gửi</button>
    </form>
    <?php return ob_get_clean();
}
