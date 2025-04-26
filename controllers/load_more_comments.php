<?php
session_start();
include '../config/config.php';
include '../utils/formatTime.php';
include '../services/users.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $post_id = $_POST['post_id'] ?? null;
    $parent_id = $_POST['parent_id'] ?? null;
    $offset = (int)$_POST['offset'] ?? 0;
    $limit = (int)$_POST['limit'] ?? 5;
    
    // Initialize user cache
    $userCache = [];
    
    if ($post_id) {
        // Get more comments
        $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $query->bind_param("ssii", $post_id, $parent_id, $limit, $offset);
        $query->execute();
        $comments = $query->get_result();
        
        $html = '';
        $count = 0;
        
        ob_start();
        while ($row = $comments->fetch_assoc()) {
            $count++;
            $userName = getUserName($conn, $row['user_id'], $userCache);
            $userAvatar = getUserAvatar($conn, $row['user_id'], $userCache);
            $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
            
            // Check if this comment has replies
            $has_replies = false;
            $reply_check = $conn->prepare("SELECT COUNT(*) FROM comment WHERE parent_id = ?");
            $reply_check->bind_param("s", $row['id']);
            $reply_check->execute();
            $reply_count = $reply_check->get_result()->fetch_row()[0];
            if ($reply_count > 0) {
                $has_replies = true;
            }
    
            echo '<div class="comment-container mb-3" id="comment-' . $row['id'] . '">';
            
            if ($parent_id === NULL) {
                // Main parent comment
                echo '<div class="card border-0 shadow-sm">';
            } else {
                // Child comment with connection line to parent
                echo '<div class="card border-0 bg-light position-relative">';
                // Improved horizontal line connecting to the vertical line
                echo '<div class="comment-connector-horizontal position-absolute" style="height: 2px; background-color: #dee2e6; width: 24px; top: 30px; left: -24px;"></div>';
            }
            
            echo '<div class="card-body py-3">';
            
            // Comment header 
            echo '<div class="d-flex justify-content-between align-items-center mb-2">';
            echo '<div class="d-flex align-items-center">';
            echo '<div class="comment-avatar me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; overflow: hidden;">';
            
            if (filter_var($userAvatar, FILTER_VALIDATE_URL)) {
                echo '<img src="' . htmlspecialchars($userAvatar) . '" alt="Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">';
            } else {
                echo '<div class="bg-primary text-white w-100 h-100 d-flex align-items-center justify-content-center" style="font-weight: bold;">' . substr($userName, 0, 1) . '</div>';
            }
            
            echo '</div>';
            echo '<div>';
            echo '<div class="fw-bold">' . htmlspecialchars($userName) . '</div>';
            echo '<div class="text-muted small time-elapsed" data-time="' . $row['created_at'] . '">' . timeAgo($row['created_at']) . '</div>';
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
                echo '<div class="d-flex justify-content-end action-buttons">';
                echo '<button type="button" class="btn btn-outline-secondary me-2 cancel-edit" data-id="' . $row['id'] . '">Hủy</button>';
                echo '<button type="submit" class="btn btn-primary save-edit">Lưu</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
            }
            
            // Reply button and form
            if (isset($_SESSION['user_id'])) {
                echo '<div class="mt-2">';
                echo '<button class="btn btn-sm btn-light toggle-reply" data-id="' . $row['id'] . '"><i class="bi bi-reply me-1"></i>Trả lời</button>';
                
                if ($has_replies) {
                    echo '<span class="ms-2 badge bg-light text-dark reply-count">' . $reply_count . ' phản hồi</span>';
                }
                
                echo '<div class="reply-form d-none mt-2" id="reply-form-' . $row['id'] . '">';
                echo '<form method="post" action="../controllers/add_comment.php">';
                echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
                echo '<input type="hidden" name="parent_id" value="' . $row['id'] . '">';
                echo '<div class="position-relative">';
                echo '<textarea name="content" class="form-control rounded-pill pe-5 py-2 auto-expand-textarea" style="resize: none; overflow-y: hidden;" required placeholder="Viết phản hồi..." rows="1"></textarea>';
                echo '<button type="submit" class="btn position-absolute end-0 mt-1 me-2 send-button" style="background: none; border: none; color: #0d6efd; transition: transform 0.3s;">';
                echo '<i class="bi bi-send-fill"></i>';
                echo '</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>'; // card-body
            echo '</div>'; // card
    
            // Display nested replies placeholder
            if ($has_replies) {
                echo '<div class="ms-3 ps-4 position-relative replies-container" id="replies-' . $row['id'] . '">';
                echo '<div class="comment-tree-line position-absolute" style="width: 2px; background-color: #dee2e6; top: 0; bottom: 0; left: 0;"></div>';
                
                // For replies loaded in AJAX, we don't pre-load any replies
                // Add load replies button
                echo '<div class="mt-2 mb-2 load-more-container ps-2">';
                echo '<button class="btn btn-sm btn-outline-primary load-more-replies" 
                      data-parent-id="' . $row['id'] . '"
                      data-post-id="' . $post_id . '"
                      data-offset="0">';
                echo 'Xem ' . $reply_count . ' phản hồi';
                echo '</button>';
                
                echo '</div>';
                
                echo '</div>';
            } else {
                echo '<div class="ms-3 ps-4" id="replies-' . $row['id'] . '"></div>';
            }
            
            echo '</div>'; // comment-container
        }
        $html = ob_get_clean();
        
        // Get total count for pagination
        if ($parent_id === null) {
            $query = $conn->prepare("SELECT COUNT(id) FROM comment WHERE post_id = ? AND parent_id IS NULL");
            $query->bind_param("s", $post_id);
        } else {
            $query = $conn->prepare("SELECT COUNT(id) FROM comment WHERE post_id = ? AND parent_id = ?");
            $query->bind_param("ss", $post_id, $parent_id);
        }
        $query->execute();
        $total = $query->get_result()->fetch_row()[0];
        
        // Calculate remaining comments
        $remaining = $total - ($offset + $count);
        
        // Response data
        $response = [
            'status' => 'success',
            'html' => $html,
            'count' => $count,
            'offset' => $offset + $count,
            'remaining' => $remaining,
            'hasMore' => $remaining > 0,
        ];
        
        echo json_encode($response);
        exit;
    }
}

// If not AJAX or missing parameters
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>