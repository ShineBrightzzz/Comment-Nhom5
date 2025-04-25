<?php
session_start();
include '../config/config.php';
include '../layouts/header.php';
include '../utils/formatTime.php';

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

function getUserInfo($conn, $user_id, &$userCache) {
    if (isset($userCache[$user_id])) {
        return $userCache[$user_id];
    }

    $query = $conn->prepare("SELECT name, avatar FROM user WHERE id = ?");
    $query->bind_param("s", $user_id);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();

    $userInfo = [
        'name' => $result['name'] ?? 'Người dùng',
        'avatar' => $result['avatar'] ?? 'default-avatar.png'
    ];
    
    $userCache[$user_id] = $userInfo;
    return $userInfo;
}

function getUserName($conn, $user_id, &$userCache) {
    $userInfo = getUserInfo($conn, $user_id, $userCache);
    return $userInfo['name'];
}

// Add function to get user avatar
function getUserAvatar($conn, $user_id, &$userCache) {
    $userInfo = getUserInfo($conn, $user_id, $userCache);
    return $userInfo['avatar'];
}



function displayComments($conn, $post_id, $parent_id = NULL) {
    global $userCache;
    if (!isset($userCache)) {
        $userCache = [];
    }
    
    $comments = getComments($conn, $post_id, $parent_id);
    while ($row = $comments->fetch_assoc()) {
        $userName = getUserName($conn, $row['user_id'], $userCache);
        $userAvatar = getUserAvatar($conn, $row['user_id'], $userCache);
        $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
        $formattedTime = timeAgo($row['created_at']);

        echo '<div class="comment-container mb-3" id="comment-' . $row['id'] . '">';
        
        if ($parent_id === NULL) {
            echo '<div class="card border-0 shadow-sm">';
        } else {
            echo '<div class="card border-0 bg-light">';
        }
        
        echo '<div class="card-body py-3">';
        
        // Comment header with user info and time
        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
        echo '<div class="d-flex align-items-center">';
        echo '<div class="comment-avatar me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; overflow: hidden;">';
        
        // Check if avatar is a URL or a default value
        if (filter_var($userAvatar, FILTER_VALIDATE_URL)) {
            // If it's a valid URL, use it directly
            echo '<img src="' . htmlspecialchars($userAvatar) . '" alt="Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">';
        } else {
            // If not a URL (like "default-avatar.png"), use a placeholder or default avatar service
            echo '<div class="bg-primary text-white w-100 h-100 d-flex align-items-center justify-content-center" style="font-weight: bold;">' . substr($userName, 0, 1) . '</div>';
        }
        
        echo '</div>';
        echo '<div>';
        echo '<div class="fw-bold">' . htmlspecialchars($userName) . '</div>';
        echo '<div class="text-muted small">' . $formattedTime . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Edit/Delete buttons for comment owner
        if ($isOwner) {
            echo '<div class="dropdown">';
            echo '<button class="btn btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>';
            echo '<ul class="dropdown-menu dropdown-menu-end">';
            echo '<li><button class="dropdown-item edit-comment" data-id="' . $row['id'] . '"><i class="bi bi-pencil me-2"></i>Sửa</button></li>';
            echo '<li><button class="dropdown-item text-danger delete-comment" data-id="' . $row['id'] . '"><i class="bi bi-trash me-2"></i>Xóa</button></li>';
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Comment content
        echo '<div class="comment-content" id="content-' . $row['id'] . '">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
        
        // Edit form (hidden by default)
        if ($isOwner) {
            echo '<div class="edit-form d-none" id="edit-form-' . $row['id'] . '">';
            echo '<form method="post" action="../controllers/edit_comment.php" class="mt-2">';
            echo '<input type="hidden" name="comment_id" value="' . $row['id'] . '">';
            echo '<textarea name="content" class="form-control mb-2 auto-expand-textarea" style="resize: none; min-height: 60px;" rows="3">' . htmlspecialchars($row['content']) . '</textarea>';
            echo '<div class="d-flex justify-content-end">';
            echo '<button type="button" class="btn btn-link cancel-edit" data-id="' . $row['id'] . '">Hủy</button>';
            echo '<button type="submit" class="btn btn-primary btn-sm">Lưu</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
        }
        
        // Reply button and form
        if (isset($_SESSION['user_id'])) {
            echo '<div class="mt-2">';
            echo '<button class="btn btn-sm btn-light toggle-reply" data-id="' . $row['id'] . '"><i class="bi bi-reply me-1"></i>Trả lời</button>';
            echo '<div class="reply-form d-none mt-2" id="reply-form-' . $row['id'] . '">';
            echo '<form method="post" action="../controllers/add_comment.php">';
            echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
            echo '<input type="hidden" name="parent_id" value="' . $row['id'] . '">';
            echo '<div class="position-relative">';
            echo '<textarea name="content" class="form-control rounded-pill pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết phản hồi..." rows="1"></textarea>';
            echo '<button type="submit" class="btn position-absolute top-0 end-0 mt-1 me-2" style="background: none; border: none; color: #0d6efd;">';
            echo '<i class="bi bi-send-fill"></i>';
            echo '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>'; // card-body
        echo '</div>'; // card

        // Display nested replies with indentation
        echo '<div class="ms-4 ps-2">';
        displayComments($conn, $post_id, $row['id']);
        echo '</div>';
        
        echo '</div>'; // comment-container
    }
}
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
                <form method="post" action="../controllers/add_comment.php" class="comment-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <div class="mb-3">
                        <div class="position-relative">
                            <textarea name="content" class="form-control rounded-pill pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết bình luận..." rows="1"></textarea>
                            <button type="submit" class="btn position-absolute top-0 end-0 mt-1 me-2" style="background: none; border: none; color: #0d6efd;">
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

<script src="https://www.google.com/recaptcha/api.js"></script>

<script src="../utils/scripts.js"></script>
