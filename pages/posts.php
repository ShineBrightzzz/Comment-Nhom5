<?php
session_start();
include '../config/config.php';
include '../layouts/header.php';

$error = $_GET['error'] ?? null;
$post_id = $_GET['posts'] ?? null;

$query = $conn->prepare("SELECT * FROM post WHERE id = ?");
$query->bind_param("s", $post_id);
$query->execute();
$post = $query->get_result()->fetch_assoc();

function getComments($conn, $post_id, $parent_id = NULL) {
    $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC");
    $query->bind_param("ss", $post_id, $parent_id);
    $query->execute();
    return $query->get_result();
}

function getUserName($conn, $user_id, &$userCache) {
    if (isset($userCache[$user_id])) {
        return $userCache[$user_id];
    }

    $query = $conn->prepare("SELECT name FROM user WHERE id = ?");
    $query->bind_param("s", $user_id);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();

    $name = $result['name'] ?? 'Người dùng';
    $userCache[$user_id] = $name;
    return $name;
}

function displayComments($conn, $post_id, $parent_id = NULL) {
    $comments = getComments($conn, $post_id, $parent_id);
    while ($row = $comments->fetch_assoc()) {
        $userName = getUserName($conn, $row['user_id'], $userCache);

        echo "<div class='ms-4 border-start ps-3 mt-3'>";
        echo "<strong>" . htmlspecialchars($userName) . "</strong>: " . nl2br(htmlspecialchars($row['content']));
        echo "<div class='text-muted small'>" . $row['created_at'] . "</div>";

        // Kiểm tra nếu tham số 'edit_comment_id' có trong URL, tức là người dùng đang sửa bình luận
               // Kiểm tra nếu tham số 'edit_comment_id' có trong URL, tức là người dùng đang sửa bình luận
               $edit_comment_id = $_GET['edit_comment_id'] ?? null;
               if ($edit_comment_id == $row['id']) {
                   // Hiển thị form sửa bình luậns
                   echo '
                       <form method="post" action="../controllers/edit_comment.php" class="mt-2 mb-3">
                           <input type="hidden" name="comment_id" value="' . $row['id'] . '">
                           <input type="hidden" name="post_id" value="' . $post_id . '">
                           <textarea name="content" class="form-control rounded-pill pe-5 py-2" required>' . htmlspecialchars($row['content']) . '</textarea>
                           
                           <!-- Di chuyển nút Lưu thay đổi và Hủy xuống dưới -->
                           <div class="mt-3">
                               <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                               <a href="posts.php?posts=' . $post_id . '" class="btn btn-secondary ms-2">Hủy</a>
                           </div>
                       </form>
                   ';
               } else {
                   // Nếu không đang sửa, hiển thị nút "Sửa" và "Xóa"
                   if (isset($_SESSION['user_id'])) {
                       echo '
                           <div class="d-flex justify-content-end mt-2">
                                <a href="?posts=' . $post_id . '&edit_comment_id=' . $row['id'] . '" class="btn btn-warning btn-sm me-2">Sửa</a>
                                <a href="../controllers/delete_comment.php?comment_id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Bạn có chắc muốn xóa bình luận này không?\');">Xóa</a>
                               
                           </div>
                       ';
                   }
               }
       

        // Hiển thị các bình luận trả lời (nếu có)
        displayComments($conn, $post_id, $row['id']);
        echo "</div>";
    }
}
?>

<div class="container my-5">    
<?php if (!empty($post) && is_array($post)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h1 class="card-title"><?php echo htmlspecialchars($post['title'] ?? 'Không có tiêu đề'); ?></h1>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'] ?? 'Không có nội dung')); ?></p>
        </div>
    </div>

    <div class="mb-4">
        <h2>Bình luận</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="../controllers/add_comment.php" class="mb-4">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <div class="mb-3">
                <div class="position-relative">
                    <textarea name="content" class="form-control rounded-pill pe-5 py-2" required placeholder="Viết bình luận..." rows="1"></textarea>
                    <button type="submit" class="btn btn-primary position-absolute top-50 end-0 translate-middle-y me-2" style="border-radius: 50%;">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
            <?php if ($error == 'limit_1'): ?>
                <div class="alert alert-warning">Bạn đã bình luận quá nhiều trong khoảng thời gian ngắn. Vui lòng xác nhận.</div>
                <div class="g-recaptcha mb-2" data-sitekey="6LevCQsrAAAAAIYq4LfTqGrkkQ621YLLZmn_zMYJ"></div>
            <?php elseif ($error == 'limit_2'): ?>
                <div class="alert alert-warning">Bạn đã bình luận quá giống nhau. Vui lòng xác nhận.</div>
                <div class="g-recaptcha mb-2" data-sitekey="6LevCQsrAAAAAIYq4LfTqGrkkQ621YLLZmn_zMYJ"></div>
            <?php elseif ($error == 'recaptcha'): ?>
                <div class="alert alert-danger">Vui lòng xác minh bạn là con người.</div>
            <?php endif; ?>
        </form>
        <?php else: ?>
            <p>Vui lòng <a href="/Comment-Nhom5/login">đăng nhập</a> để bình luận.</p>
        <?php endif; ?>
    </div>

    <div>
        <h4>Danh sách bình luận:</h4>
        <?php displayComments($conn, $post_id); ?>
    </div>
<?php else: ?>
    <div class="alert alert-danger">Bài viết không tồn tại.</div>
<?php endif; ?>
</div>

<script src="https://www.google.com/recaptcha/api.js"></script>
