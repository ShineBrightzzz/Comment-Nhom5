<?php
    function getComments($conn, $post_id, $parent_id = NULL, $limit = NULL, $offset = 0) {
        if ($limit !== NULL) {
            $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $query->bind_param("ssii", $post_id, $parent_id, $limit, $offset);
        } else {
            $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC");
            $query->bind_param("ss", $post_id, $parent_id);
        }
        $query->execute();
        return $query->get_result();
    }

    function countComments($conn, $post_id, $parent_id = NULL) {
        $query = $conn->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_id <=> ?");
        $query->bind_param("ss", $post_id, $parent_id);
        $query->execute();
        return $query->get_result()->fetch_row()[0];
    }

    function displayComments($conn, $post_id, $parent_id = NULL, $limit = 5, $initial = true) {
        global $userCache;
        if (!isset($userCache)) {
            $userCache = [];
        }
        
        // Get total comment count for the current level
        $total_comments = countComments($conn, $post_id, $parent_id);
        
        // Get limited comments for display
        $comments = getComments($conn, $post_id, $parent_id, $limit, 0);
        
        // Count how many comments we're displaying
        $displayed_comments = 0;

        while ($row = $comments->fetch_assoc()) {
            $displayed_comments++;
            $userName = getUserName($conn, $row['user_id'], $userCache);
            $userAvatar = getUserAvatar($conn, $row['user_id'], $userCache);
            $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
            $formattedTime = timeAgo($row['created_at']);
            
            // Check if this comment has replies
            $reply_count = countComments($conn, $post_id, $row['id']);
            $has_replies = $reply_count > 0;
    
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
                // Change to ajax-comment-form class for AJAX submission
                echo '<form method="post" action="../controllers/add_comment.php" class="ajax-comment-form">';
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
    
            // Display nested replies with indentation and proper connecting lines
            if ($has_replies) {
                // This container will hold all the child comments
                echo '<div class="ms-3 ps-4 position-relative replies-container" id="replies-' . $row['id'] . '">';
                
                // Add continuous vertical line from parent to connect all children - runs the full height
                echo '<div class="comment-tree-line position-absolute" style="width: 2px; background-color: #dee2e6; top: 0; bottom: 0; left: 0;"></div>';
                
                // For replies, only show 3 initially
                $reply_limit = 3;
                
                // Recursively display child comments with a limit
                displayComments($conn, $post_id, $row['id'], $reply_limit, true);
                
                // If there are more replies than shown, add "Load More" button
                if ($reply_count > $reply_limit) {
                    echo '<div class="mt-2 mb-2 load-more-container ps-2">';
                    echo '<button class="btn btn-sm btn-outline-primary load-more-replies" data-parent-id="' . $row['id'] . '" data-post-id="' . $post_id . '" data-offset="' . $reply_limit . '">';
                    echo 'Xem thêm ' . ($reply_count - $reply_limit) . ' phản hồi';
                    echo '</button>';
                    echo '</div>';
                }
                
                echo '</div>';
            } else {
                // Even if there are no replies, we'll create a placeholder for consistent styling
                echo '<div class="ms-3 ps-4" id="replies-' . $row['id'] . '">';
                displayComments($conn, $post_id, $row['id'], 3, true);
                echo '</div>';
            }
            
            echo '</div>'; // comment-container
        }
        
        // Add "Load More" button for main comments if there are more to show
        if ($parent_id === NULL && $initial && $total_comments > $displayed_comments) {
            echo '<div class="mt-3 mb-4 load-more-container">';
            echo '<button class="btn btn-outline-primary load-more-comments" data-post-id="' . $post_id . '" data-offset="' . $displayed_comments . '">';
            echo 'Xem thêm ' . ($total_comments - $displayed_comments) . ' bình luận';
            echo '</button>';
            echo '</div>';
        }
    }
?>