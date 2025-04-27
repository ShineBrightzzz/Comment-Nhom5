<?php
session_start();
include '../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
include '../utils/formatTime.php';
include '../services/users.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();


// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'error',
                        'type' => 'limit_1',
                        'message' => 'Bạn đã bình luận quá nhiều trong thời gian ngắn. Vui lòng xác nhận.',
                        'redirect' => "/Comment-Nhom5/posts/{$post_id}?error=limit_1"
                    ]);
                    exit();
                } else {
                    header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_1");
                    exit();
                }
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
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'error',
                        'type' => 'limit_2',
                        'message' => 'Bạn đã bình luận quá giống nhau. Vui lòng xác nhận.',
                        'redirect' => "/Comment-Nhom5/posts/{$post_id}?error=limit_2"
                    ]);
                    exit();
                } else {
                    header("Location: /Comment-Nhom5/posts/{$post_id}?error=limit_2");
                    exit();
                }
            }
            validateCaptcha($_POST['g-recaptcha-response']);
        }
    }

    // Insert bình luận
    $query = $conn->prepare("INSERT INTO comment (id, content, user_id, post_id, parent_id) VALUES (UUID(), ?, ?, ?, ?)");
    $query->bind_param("ssss", $content, $user_id, $post_id, $parent_id);
    
    if ($query->execute()) {
        // If comment added successfully
        $comment_id = $conn->insert_id;
        
        // Get the new comment data
        $query = $conn->prepare("SELECT * FROM comment WHERE id = LAST_INSERT_ID()");
        $query->execute();
        $new_comment = $query->get_result()->fetch_assoc();
        
        if ($isAjax) {
            // Get user info for the response
            $userCache = [];
            $userName = getUserName($conn, $user_id, $userCache);
            $userAvatar = getUserAvatar($conn, $user_id, $userCache);
            
            // For AJAX responses, return the comment HTML
            ob_start();
            
            // Generate HTML based on whether it's a reply or a main comment
            if ($parent_id === NULL) {
                // Main comment
                ?>
                <div class="comment-container mb-3" id="comment-<?= $new_comment['id'] ?>">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="comment-avatar me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; overflow: hidden;">
                                        <?php if (filter_var($userAvatar, FILTER_VALIDATE_URL)): ?>
                                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-primary text-white w-100 h-100 d-flex align-items-center justify-content-center" style="font-weight: bold;"><?= substr($userName, 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($userName) ?></div>
                                        <div class="text-muted small time-elapsed" data-time="<?= $new_comment['created_at'] ?>"><?= timeAgo($new_comment['created_at']) ?></div>
                                    </div>
                                </div>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button class="dropdown-item edit-comment" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-pencil me-2"></i>Sửa</button></li>
                                        <li><button class="dropdown-item text-danger delete-comment" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-trash me-2"></i>Xóa</button></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="comment-content" id="content-<?= $new_comment['id'] ?>"><?= nl2br(htmlspecialchars($content)) ?></div>
                            
                            <div class="edit-form d-none" id="edit-form-<?= $new_comment['id'] ?>">
                                <form method="post" action="../controllers/edit_comment.php" class="mt-2">
                                    <input type="hidden" name="comment_id" value="<?= $new_comment['id'] ?>">
                                    <textarea name="content" class="form-control mb-2 auto-expand-textarea" style="resize: none; min-height: 60px;" rows="3"><?= htmlspecialchars($content) ?></textarea>
                                    <div class="d-flex justify-content-end action-buttons">
                                        <button type="button" class="btn btn-outline-secondary me-2 cancel-edit" data-id="<?= $new_comment['id'] ?>">Hủy</button>
                                        <button type="submit" class="btn btn-primary save-edit">Lưu</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="mt-2">
                                <button class="btn btn-sm btn-light toggle-reply" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-reply me-1"></i>Trả lời</button>
                                
                                <div class="reply-form d-none mt-2" id="reply-form-<?= $new_comment['id'] ?>">
                                    <form method="post" action="../controllers/add_comment.php" class="ajax-comment-form">
                                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                        <input type="hidden" name="parent_id" value="<?= $new_comment['id'] ?>">
                                        <div class="position-relative">
                                            <textarea name="content" class="form-control rounded-pill pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết phản hồi..." rows="1"></textarea>
                                            <button type="submit" class="btn position-absolute end-0 mt-1 me-2 send-button" style="background: none; border: none; color: #0d6efd; transition: transform 0.3s;">
                                                <i class="bi bi-send-fill"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ms-3 ps-4" id="replies-<?= $new_comment['id'] ?>"></div>
                </div>
                <?php
            } else {
                // Reply comment
                ?>
                <div class="comment-container mb-3" id="comment-<?= $new_comment['id'] ?>">
                    <div class="card border-0 bg-light position-relative">
                        <div class="comment-connector-horizontal position-absolute" style="height: 2px; background-color: #dee2e6; width: 24px; top: 30px; left: -24px;"></div>
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="comment-avatar me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; overflow: hidden;">
                                        <?php if (filter_var($userAvatar, FILTER_VALIDATE_URL)): ?>
                                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-primary text-white w-100 h-100 d-flex align-items-center justify-content-center" style="font-weight: bold;"><?= substr($userName, 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($userName) ?></div>
                                        <div class="text-muted small time-elapsed" data-time="<?= $new_comment['created_at'] ?>"><?= timeAgo($new_comment['created_at']) ?></div>
                                    </div>
                                </div>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button class="dropdown-item edit-comment" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-pencil me-2"></i>Sửa</button></li>
                                        <li><button class="dropdown-item text-danger delete-comment" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-trash me-2"></i>Xóa</button></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="comment-content" id="content-<?= $new_comment['id'] ?>"><?= nl2br(htmlspecialchars($content)) ?></div>
                            
                            <div class="edit-form d-none" id="edit-form-<?= $new_comment['id'] ?>">
                                <form method="post" action="../controllers/edit_comment.php" class="mt-2">
                                    <input type="hidden" name="comment_id" value="<?= $new_comment['id'] ?>">
                                    <textarea name="content" class="form-control mb-2 auto-expand-textarea" style="resize: none; min-height: 60px;" rows="3"><?= htmlspecialchars($content) ?></textarea>
                                    <div class="d-flex justify-content-end action-buttons">
                                        <button type="button" class="btn btn-outline-secondary me-2 cancel-edit" data-id="<?= $new_comment['id'] ?>">Hủy</button>
                                        <button type="submit" class="btn btn-primary save-edit">Lưu</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="mt-2">
                                <button class="btn btn-sm btn-light toggle-reply" data-id="<?= $new_comment['id'] ?>"><i class="bi bi-reply me-1"></i>Trả lời</button>
                                
                                <div class="reply-form d-none mt-2" id="reply-form-<?= $new_comment['id'] ?>">
                                    <form method="post" action="../controllers/add_comment.php" class="ajax-comment-form">
                                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                        <input type="hidden" name="parent_id" value="<?= $new_comment['id'] ?>">
                                        <div class="position-relative">
                                            <textarea name="content" class="form-control rounded-pill pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết phản hồi..." rows="1"></textarea>
                                            <button type="submit" class="btn position-absolute end-0 mt-1 me-2 send-button" style="background: none; border: none; color: #0d6efd; transition: transform 0.3s;">
                                                <i class="bi bi-send-fill"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            
            $comment_html = ob_get_clean();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'html' => $comment_html,
                'comment' => $new_comment,
                'parent_id' => $parent_id
            ]);
            exit();
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Không thể thêm bình luận'
            ]);
            exit();
        }
    }
}

// Hàm kiểm tra session captcha còn hiệu lực không
function isCaptchaVerified()
{
    return isset($_SESSION['captcha_verified_until']) && $_SESSION['captcha_verified_until'] > time();
}

// Hàm xác minh captcha
function validateCaptcha($recaptcha_response)
{
    $secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    
    if (empty($secret)) {
        error_log('reCAPTCHA secret key is missing');
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    }
    
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha_response}");
    
    if (!$verifyResponse) {
        error_log('Could not connect to reCAPTCHA verification service');
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    }
    
    $responseData = json_decode($verifyResponse);
    
    if (!$responseData->success) {
        error_log('reCAPTCHA verification failed: ' . json_encode($responseData));
        header("Location: /Comment-Nhom5/posts/{$_POST['post_id']}?error=recaptcha");
        exit();
    }
    
    // Nếu xác minh thành công, lưu session trong 10 phút
    $_SESSION['captcha_verified_until'] = time() + 600;
    
    return true;
}

if (!$isAjax) {
    header("Location: /Comment-Nhom5/posts/{$post_id}");
    exit();
}
?>
