<?php
session_start();
include 'config.php';

$post_id = $_GET['id'] ?? 1;
$reply_to = $_GET['reply_to'] ?? null;

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

function displayComments($conn, $post_id, $parent_id = NULL, $level = 0, $reply_to = null) {
    $comments = getComments($conn, $post_id, $parent_id);
    $index = 0;

    while ($row = $comments->fetch_assoc()) {
        $margin = ($level % 2 == 0 ? $level : $level - 1) * 30;
        echo "<div class='card mb-3' style='margin-left: {$margin}px;'>
                <div class='card-body'>";

        echo "<h6 class='card-subtitle mb-1 text-muted'>" . htmlspecialchars($row['user_id']) . "</h6>";

        if (isset($_GET['edit_id']) && $_GET['edit_id'] == $row['id']) {
            $escapedContent = htmlspecialchars($row['content'], ENT_QUOTES);
            echo "<form method='post' action='edit_comment.php'>
                    <input type='hidden' name='comment_id' value='{$row['id']}'>
                    <div class='mb-2'>
                        <textarea class='form-control' name='content' required rows='3'>{$escapedContent}</textarea>
                    </div>
                    <button type='submit' class='btn btn-primary btn-sm'>Lưu</button>
                    <a href='index1.php?id={$post_id}' class='btn btn-secondary btn-sm'>Hủy</a>
                  </form>";
        } else {
            echo "<p class='card-text'>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
            echo "<small class='text-muted'>" . $row['created_at'] . "</small><br>";

            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']) {
                echo "<div class='mt-2'>
                        <a href='index1.php?id={$post_id}&edit_id={$row['id']}' class='btn btn-outline-secondary btn-sm me-2'>Sửa</a>
                        <a href='delete_comment.php?id={$row['id']}&post_id={$post_id}' class='btn btn-outline-danger btn-sm' onclick=\"return confirm('Bạn có chắc muốn xóa không?');\">Xóa</a>
                      </div>";
            }

            if (isset($_SESSION['user_id'])) {
                echo "<div class='mt-2'>
                        <a href='index1.php?id={$post_id}&reply_to={$row['id']}' class='btn btn-link btn-sm'>Trả lời</a>
                      </div>";
            }

            if ($reply_to == $row['id']) {
                // Gắn tag user
                $reply_user = htmlspecialchars($row['user_id']);
                echo "<form method='post' action='comment.php' class='mt-3'>
                        <input type='hidden' name='post_id' value='{$post_id}'>
                        <input type='hidden' name='parent_id' value='{$row['id']}'>
                        <div class='mb-2'>
                            <textarea class='form-control' name='content' required rows='2'>@$reply_user </textarea>
                        </div>
                        <button type='submit' class='btn btn-success btn-sm'>Gửi trả lời</button>
                        <a href='index1.php?id={$post_id}' class='btn btn-outline-secondary btn-sm'>Hủy</a>
                      </form>";
            }
        }
        echo "</div></div>";

        // Chỉ hiển thị 1 comment con đầu tiên và có nút ẩn/hiện
        $child_comments = getComments($conn, $post_id, $row['id']);
        $children = [];
        while ($child = $child_comments->fetch_assoc()) {
            $children[] = $child;
        }

        if (count($children) > 0) {
            $first = array_shift($children);
            displayComments($conn, $post_id, $first['id'], $level + 1, $reply_to);

            if (count($children) > 0) {
                echo "<div class='collapse' id='more-replies-{$row['id']}'>";
                foreach ($children as $child) {
                    displayComments($conn, $post_id, $child['id'], $level + 1, $reply_to);
                }
                echo "</div>
                    <button class='btn btn-link btn-sm' type='button' data-bs-toggle='collapse' data-bs-target='#more-replies-{$row['id']}'>Xem thêm trả lời</button>
                    <button class='btn btn-link btn-sm collapse' type='button' data-bs-toggle='collapse' data-bs-target='#more-replies-{$row['id']}'>Thu gọn</button>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        textarea.form-control::placeholder {
            color: #aaa;
        }
        textarea.form-control {
            font-size: 14px;
        }
        .card-text span.tag {
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h2>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        </div>
    </div>

    <h4>Bình luận</h4>
    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="comment.php" class="mb-4">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <div class="mb-2">
                <textarea name="content" class="form-control" required rows="3" placeholder="Viết bình luận..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Gửi bình luận</button>
        </form>
    <?php else: ?>
        <p>Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</p>
    <?php endif; ?>

    <h5 class="mt-4">Danh sách bình luận:</h5>
    <?php displayComments($conn, $post_id, NULL, 0, $reply_to); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
