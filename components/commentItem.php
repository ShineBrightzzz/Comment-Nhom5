<?php
function renderCommentItem($conn, $post_id, $comment) {
    ob_start(); ?>
    <div class="comment-item">
        <strong><?= htmlspecialchars($comment['user_id']) ?></strong>: <?= htmlspecialchars($comment['content']) ?>
        <br><small><?= $comment['created_at'] ?></small>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="post" action="comment.php" class="reply-form">
                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                <textarea name="content" required placeholder="Trả lời..."></textarea>
                <button type="submit">Gửi</button>
            </form>
        <?php endif; ?>

        <?= renderCommentList($conn, $post_id, $comment['id']) ?>
    </div>
    <?php return ob_get_clean();
}
