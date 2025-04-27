$(document).ready(function () {
    // AJAX Comment Submission
    $(document).on('submit', '.comment-form, .ajax-comment-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnHtml = submitBtn.html();
        const formData = form.serialize();
        const postId = form.find('input[name="post_id"]').val();
        const parentId = form.find('input[name="parent_id"]').val();
        const textareaContent = form.find('textarea[name="content"]');
        
        // Disable the submit button and show loading state
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status"></span>').prop('disabled', true);
        
        $.ajax({
            url: '/Comment-Nhom5/controllers/add_comment.php',
            method: 'POST',
            data: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Clear the textarea
                    textareaContent.val('');
                    
                    if (parentId) {
                        // For replies, append the new comment to its parent's replies container
                        const repliesContainer = $('#replies-' + parentId);
                        
                        // If this is the first reply, we may need to setup the container properly
                        if (repliesContainer.children().length === 0) {
                            // Add the tree line if not already present
                            repliesContainer.addClass('position-relative');
                            repliesContainer.prepend('<div class="comment-tree-line position-absolute" style="width: 2px; background-color: #dee2e6; top: 0; bottom: 0; left: 0;"></div>');
                        }
                        
                        // Add the new reply to the beginning of the replies container
                        repliesContainer.prepend(response.html);
                        
                        // Hide the reply form
                        $('#reply-form-' + parentId).addClass('d-none');
                        
                        // Update reply count
                        const replyCountBadge = repliesContainer.closest('.comment-container').find('.reply-count');
                        if (replyCountBadge.length > 0) {
                            // Extract current number and increment
                            let currentCount = parseInt(replyCountBadge.text().split(' ')[0]);
                            replyCountBadge.text((currentCount + 1) + ' phản hồi');
                        } else {
                            // Add new reply count badge
                            const replyBtn = repliesContainer.closest('.comment-container').find('.toggle-reply');
                            replyBtn.after('<span class="ms-2 badge bg-light text-dark reply-count">1 phản hồi</span>');
                        }
                    } else {
                        // For new main comments, prepend to the comments section
                        $('.comments-section h4').after(response.html);
                    }
                    
                    // Initialize the new comment interactive elements
                    initTextareas();
                    
                    // Reset the button
                    submitBtn.html(originalBtnHtml).prop('disabled', false);
                    
                    // Reset textarea height
                    textareaContent.css('height', 'auto');
                } else if (response.type === 'limit_1' || response.type === 'limit_2' || response.type === 'recaptcha') {
                    // Chuyển hướng người dùng khi gặp lỗi cần xác minh captcha
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        // Fallback nếu không có URL chuyển hướng rõ ràng
                        window.location.href = `/Comment-Nhom5/posts/${postId}?error=${response.type}`;
                    }
                } else {
                    // Handle other errors
                    alert('Có lỗi xảy ra khi thêm bình luận');
                    submitBtn.html(originalBtnHtml).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error submitting comment:', error);
                alert('Có lỗi xảy ra khi gửi bình luận');
                submitBtn.html(originalBtnHtml).prop('disabled', false);
            }
        });
    });

    // AJAX Comment Edit
    $(document).on('submit', 'form[action="../controllers/edit_comment.php"]', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button.save-edit');
        const originalBtnHtml = submitBtn.html();
        const formData = form.serialize();
        const commentId = form.find('input[name="comment_id"]').val();
        const content = form.find('textarea[name="content"]').val();
        const commentContent = $(`#content-${commentId}`);
        
        // Disable the submit button and show loading state
        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status"></span>').prop('disabled', true);
        
        $.ajax({
            url: '../controllers/edit_comment.php',
            method: 'POST',
            data: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Update comment content
                    commentContent.html(response.content);
                    
                    // Hide the edit form
                    $(`#edit-form-${commentId}`).addClass('d-none');
                    commentContent.removeClass('d-none');
                    
                    // Reset the button
                    submitBtn.html(originalBtnHtml).prop('disabled', false);
                    
                    // Show success notification (optional)
                    showNotification('Bình luận đã được cập nhật thành công', 'success');
                } else {
                    // Handle errors
                    alert(response.message || 'Có lỗi xảy ra khi cập nhật bình luận');
                    submitBtn.html(originalBtnHtml).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating comment:', error);
                alert('Có lỗi xảy ra khi cập nhật bình luận');
                submitBtn.html(originalBtnHtml).prop('disabled', false);
            }
        });
    });

    // Load bình luận chính
    $(document).on('click', '.load-more-comments', function () {
        const btn = $(this);
        const postId = btn.data('postId');
        const offset = parseInt(btn.data('offset'));
        const loadMoreContainer = btn.closest('.load-more-container');
        
        console.log('Loading more comments:', postId, offset);
        
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...');
        btn.prop('disabled', true);
        
        $.ajax({
            url: '../controllers/load_more_comments.php',
            method: 'POST',
            data: {
                post_id: postId,
                offset: offset,
                limit: 5
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function (data) {
                console.log('Received data:', data);
                if (data.status === 'success') {
                    // Insert new comments before the "load more" button
                    loadMoreContainer.before(data.html);
                    
                    // Initialize textareas
                    initTextareas();
                    
                    if (data.hasMore) {
                        // Update button with new offset
                        btn.data('offset', data.offset);
                        btn.html(`Xem thêm ${data.remaining} bình luận`);
                        btn.prop('disabled', false);
                    } else {
                        // No more comments, remove button
                        loadMoreContainer.remove();
                    }
                } else {
                    console.error('Error loading more comments:', data);
                    btn.html('Có lỗi xảy ra. Thử lại');
                    btn.prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                btn.html('Có lỗi xảy ra. Thử lại');
                btn.prop('disabled', false);
            }
        });
    });

    // Load phản hồi (reply) cho từng comment
    $(document).on('click', '.load-more-replies', function () {
        const btn = $(this);
        const postId = btn.data('postId');
        const parentId = btn.data('parentId');
        const offset = parseInt(btn.data('offset'));
        const repliesContainer = $(`#replies-${parentId}`);
        const loadMoreContainer = btn.closest('.load-more-container');
        
        console.log('Loading more replies:', postId, parentId, offset);
        
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...');
        btn.prop('disabled', true);

        $.ajax({
            url: '../controllers/load_more_comments.php',
            method: 'POST',
            data: {
                post_id: postId,
                parent_id: parentId,
                offset: offset,
                limit: 5
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function (data) {
                console.log('Received reply data:', data);
                if (data.status === 'success') {
                    // For first load, clear any existing "load more" container
                    if (offset === 0 && loadMoreContainer) {
                        loadMoreContainer.remove();
                    }
                    
                    // Append new replies
                    let insertPosition = repliesContainer.find('.load-more-container');
                    if (insertPosition.length > 0) {
                        insertPosition.before(data.html);
                    } else {
                        repliesContainer.append(data.html);
                    }
                    
                    // Initialize textareas
                    initTextareas();
                    
                    if (data.hasMore) {
                        // Create or update "load more" button
                        const loadMoreHtml = `
                            <div class="mt-2 mb-2 load-more-container ps-2">
                                <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                        data-parent-id="${parentId}" 
                                        data-post-id="${postId}" 
                                        data-offset="${data.offset}">
                                    Xem thêm ${data.remaining} phản hồi
                                </button>
                            </div>
                        `;
                        
                        // Remove old button if exists
                        repliesContainer.find('.load-more-container').remove();
                        
                        // Add new button
                        repliesContainer.append(loadMoreHtml);
                    }
                } else {
                    console.error('Error loading more replies:', data);
                    if (!btn.closest('body').length) {
                        // Button was removed from DOM, create a new one
                        const loadMoreHtml = `
                            <div class="mt-2 mb-2 load-more-container ps-2">
                                <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                        data-parent-id="${parentId}" 
                                        data-post-id="${postId}" 
                                        data-offset="${offset}">
                                    Có lỗi xảy ra. Thử lại
                                </button>
                            </div>
                        `;
                        repliesContainer.append(loadMoreHtml);
                    } else {
                        btn.html('Có lỗi xảy ra. Thử lại');
                        btn.prop('disabled', false);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                if (!btn.closest('body').length) {
                    // Button was removed from DOM, create a new one
                    const loadMoreHtml = `
                        <div class="mt-2 mb-2 load-more-container ps-2">
                            <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                    data-parent-id="${parentId}" 
                                    data-post-id="${postId}" 
                                    data-offset="${offset}">
                                Có lỗi xảy ra. Thử lại
                            </button>
                        </div>
                    `;
                    repliesContainer.append(loadMoreHtml);
                } else {
                    btn.html('Có lỗi xảy ra. Thử lại');
                    btn.prop('disabled', false);
                }
            }
        });
    });
    
    // Toggle reply form
    $(document).on('click', '.toggle-reply', function() {
        const commentId = $(this).data('id');
        const replyForm = $(`#reply-form-${commentId}`);
        replyForm.toggleClass('d-none');
        if (!replyForm.hasClass('d-none')) {
            replyForm.find('textarea').focus();
        }
    });
    
    // Edit comment
    $(document).on('click', '.edit-comment', function() {
        const commentId = $(this).data('id');
        $(`#content-${commentId}`).addClass('d-none');
        $(`#edit-form-${commentId}`).removeClass('d-none');
        
        // Auto-adjust height of textarea after showing the edit form
        const textarea = $(`#edit-form-${commentId}`).find('textarea');
        adjustTextareaHeight(textarea);
        textarea.focus();
        
        // Position the cursor at the end of the text
        const textLength = textarea.val().length;
        textarea[0].setSelectionRange(textLength, textLength);
    });
    
    // Cancel edit
    $(document).on('click', '.cancel-edit', function() {
        const commentId = $(this).data('id');
        $(`#content-${commentId}`).removeClass('d-none');
        $(`#edit-form-${commentId}`).addClass('d-none');
    });
    
    // Delete comment
    $(document).on('click', '.delete-comment', function() {
        if (confirm('Bạn có chắc muốn xóa bình luận này?')) {
            const commentId = $(this).data('id');
            const commentContainer = $(`#comment-${commentId}`);
            
            $.ajax({
                url: '../controllers/delete_comment.php',
                method: 'POST',
                data: {
                    comment_id: commentId
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                dataType: 'json',
                beforeSend: function() {
                    // Hiển thị hiệu ứng đang xóa
                    commentContainer.addClass('deleting').css('opacity', '0.5');
                },
                success: function(response) {
                    console.log('Delete response:', response);
                    if (response.success) {
                        // Xóa bình luận khỏi DOM với hiệu ứng
                        commentContainer.slideUp(400, function() {
                            $(this).remove();
                        });
                    } else {
                        // Hiển thị lỗi
                        alert(response.message || 'Có lỗi xảy ra khi xóa bình luận');
                        commentContainer.removeClass('deleting').css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting comment:', error);
                    alert('Có lỗi xảy ra khi xóa bình luận');
                    commentContainer.removeClass('deleting').css('opacity', '1');
                }
            });
        }
    });
    
    // Initialize function for auto-expanding textareas
    function initTextareas() {
        $('.auto-expand-textarea').each(function() {
            if (!$(this).data('initialized')) {
                $(this).data('initialized', true);
                
                $(this).on('input', function() {
                    adjustTextareaHeight($(this));
                });
                
                adjustTextareaHeight($(this));
            }
        });
    }
    
    // Function to adjust textarea height
    function adjustTextareaHeight(textarea) {
        textarea.css('height', 'auto');
        textarea.css('height', textarea[0].scrollHeight + 'px');
        
        // Adjust send button position
        const sendButton = textarea.parent().find('.send-button');
        if (sendButton.length) {
            if (textarea[0].scrollHeight > 40) {
                sendButton.removeClass('top-0 mt-1').addClass('top-50 translate-middle-y');
            } else {
                sendButton.removeClass('top-50 translate-middle-y').addClass('top-0 mt-1');
            }
        }
    }
    
    // Function to show notification
    function showNotification(message, type = 'info') {
        const notificationId = 'toast-notification-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : 
                        type === 'error' ? 'bg-danger' : 
                        type === 'warning' ? 'bg-warning' : 'bg-info';
                        
        const toast = `
            <div id="${notificationId}" class="toast position-fixed bottom-0 end-0 m-3 ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">Thông báo</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        // Add toast to document
        $('body').append(toast);
        
        // Show the toast
        const toastElement = $(`#${notificationId}`);
        const bsToast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toastElement.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
    
    // Initialize textareas on page load
    initTextareas();
});