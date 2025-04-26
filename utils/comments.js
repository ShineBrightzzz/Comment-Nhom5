$(document).ready(function () {
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
            
            // Create a form and submit it
            const form = $('<form></form>').attr({
                'method': 'POST',
                'action': '../controllers/delete_comment.php'
            });
            
            const input = $('<input>').attr({
                'type': 'hidden',
                'name': 'comment_id',
                'value': commentId
            });
            
            form.append(input);
            $('body').append(form);
            form.submit();
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
    
    // Initialize textareas on page load
    initTextareas();
});