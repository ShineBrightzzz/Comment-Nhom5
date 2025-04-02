<?php
session_start();
include 'config.php';

// Lấy bài viết
$post_id = $_GET['id'] ?? 1;
$query = $conn->prepare("SELECT * FROM post WHERE id = ?");
$query->bind_param("s", $post_id);
$query->execute();
$post = $query->get_result()->fetch_assoc();


// Lấy danh sách bình luận
function getComments($conn, $post_id, $parent_id = NULL) {
    $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC");
    $query->bind_param("ss", $post_id, $parent_id);
    $query->execute();
    return $query->get_result();
}

function displayComments($conn, $post_id, $parent_id = NULL) {
    $comments = getComments($conn, $post_id, $parent_id);
    while ($row = $comments->fetch_assoc()) {
        echo "<div style='margin-left: 20px; border-left: 2px solid #ddd; padding: 10px;'>";
        echo "<strong>" . htmlspecialchars($row['user_id']) . "</strong>: " . htmlspecialchars($row['content']);
        echo "<br><small>" . $row['created_at'] . "</small>";

        // Nút trả lời
        if (isset($_SESSION['user_id'])) {
            echo "<form method='post' action='comment.php'>
                    <input type='hidden' name='post_id' value='{$post_id}'>
                    <input type='hidden' name='parent_id' value='{$row['id']}'>
                    <textarea name='content' required placeholder='Trả lời...'></textarea>
                    <button type='submit'>Gửi</button>
                  </form>";
        }

        // Hiển thị bình luận con
        displayComments($conn, $post_id, $row['id']);
        echo "</div>";
    }
}
?>

<?php if (!empty($post) && is_array($post)): ?>
    <h1><?php echo htmlspecialchars($post['title'] ?? 'Không có tiêu đề'); ?></h1>
    <p><?php echo htmlspecialchars($post['content'] ?? 'Không có nội dung'); ?></p>

    <h2>Bình luận</h2>
    <?php if (isset($_SESSION['user_id'])): ?>
    <form method="post" action="comment.php">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <textarea name="content" required placeholder="Viết bình luận..."></textarea>
        <button type="submit">Gửi</button>
    </form>
    <?php else: ?>
    <p>Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</p>
<?php endif; ?>

<h3>Danh sách bình luận:</h3>
<?php displayComments($conn, $post_id); ?>
<?php else: ?>
    <p>Bài viết không tồn tại.</p>
<?php endif; ?>