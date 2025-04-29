document.addEventListener('DOMContentLoaded', function() {
    // Auto-expanding textarea function
    document.querySelectorAll('.auto-expand-textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            // Reset height to auto first to allow shrinking
            this.style.height = 'auto';
            // Set the height to match the scroll height
            this.style.height = (this.scrollHeight) + 'px';
            
            // Adjust position of send button in reply forms
            const sendButton = this.parentElement.querySelector('.send-button');
            if (sendButton) {
                if (this.scrollHeight > 40) {
                    sendButton.classList.remove('top-0', 'mt-1');
                    sendButton.classList.add('top-50', 'translate-middle-y');
                } else {
                    sendButton.classList.remove('top-50', 'translate-middle-y');
                    sendButton.classList.add('top-0', 'mt-1');
                }
            }
            
            // For edit forms, reposition save buttons container if necessary
            const actionButtons = this.closest('form')?.querySelector('.action-buttons');
            if (actionButtons) {
                // Always ensure action buttons stay at the bottom
                actionButtons.style.marginTop = '8px';
            }
        });
        
        // Initial height adjustment
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });
    
    // DEBUG - Check for load more buttons on page load
    console.log('Load more comments buttons:', document.querySelectorAll('.load-more-comments').length);
    console.log('Load more replies buttons:', document.querySelectorAll('.load-more-replies').length);
    
    // Load more comments - Using event delegation for better performance with dynamically added elements
    document.addEventListener('click', function(e) {
        // Handle main comments "load more" button
        if (e.target.classList.contains('load-more-comments') || e.target.closest('.load-more-comments')) {
            const button = e.target.classList.contains('load-more-comments') ? e.target : e.target.closest('.load-more-comments');
            const postId = button.dataset.postId;
            const offset = parseInt(button.dataset.offset);
            const limit = 5; // Number of comments to load each time
            const loadMoreContainer = button.closest('.load-more-container');
            
            console.log('Loading more comments:', postId, offset, limit);
            
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...';
            button.disabled = true;
            
            // Send AJAX request
            fetch('../controllers/load_more_comments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `post_id=${postId}&offset=${offset}&limit=${limit}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                if (data.status === 'success') {
                    // Insert new comments before the "load more" button
                    loadMoreContainer.insertAdjacentHTML('beforebegin', data.html);
                    
                    // Initialize new textareas, reply buttons, etc.
                    initializeNewCommentElements();
                    
                    if (data.hasMore) {
                        // Update button with new offset
                        button.dataset.offset = data.offset;
                        button.innerHTML = `Xem thêm ${data.remaining} bình luận`;
                        button.disabled = false;
                    } else {
                        // No more comments, remove button
                        loadMoreContainer.remove();
                    }
                } else {
                    console.error('Error loading more comments:', data);
                    button.innerHTML = 'Có lỗi xảy ra. Thử lại';
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = 'Có lỗi xảy ra. Thử lại';
                button.disabled = false;
            });
        }
        
        // Handle replies "load more" button
        if (e.target.classList.contains('load-more-replies') || e.target.closest('.load-more-replies')) {
            const button = e.target.classList.contains('load-more-replies') ? e.target : e.target.closest('.load-more-replies');
            const postId = button.dataset.postId;
            const parentId = button.dataset.parentId;
            const offset = parseInt(button.dataset.offset);
            const limit = 5; // Number of replies to load each time
            const repliesContainer = document.getElementById(`replies-${parentId}`);
            const loadMoreContainer = button.closest('.load-more-container');
            
            console.log('Loading more replies:', postId, parentId, offset, limit);
            
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...';
            button.disabled = true;
            
            // Send AJAX request
            fetch('../controllers/load_more_comments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `post_id=${postId}&parent_id=${parentId}&offset=${offset}&limit=${limit}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received reply data:', data);
                if (data.status === 'success') {
                    // For first load, clear any existing "load more" container
                    if (offset === 0 && loadMoreContainer) {
                        loadMoreContainer.remove();
                    }
                    
                    // Append new replies
                    let insertPosition = repliesContainer.querySelector('.load-more-container');
                    if (insertPosition) {
                        insertPosition.insertAdjacentHTML('beforebegin', data.html);
                    } else {
                        repliesContainer.insertAdjacentHTML('beforeend', data.html);
                    }
                    
                    // Initialize new textareas, reply buttons, etc.
                    initializeNewCommentElements();
                    
                    if (data.hasMore) {
                        // Create or update "load more" button
                        const loadMoreBtn = document.createElement('div');
                        loadMoreBtn.className = 'text-center mt-2 mb-2 load-more-container';
                        loadMoreBtn.innerHTML = `
                            <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                    data-parent-id="${parentId}" 
                                    data-post-id="${postId}" 
                                    data-offset="${data.offset}">
                                Xem thêm ${data.remaining} phản hồi
                            </button>
                        `;
                        
                        // Remove old button if exists
                        const oldLoadMore = repliesContainer.querySelector('.load-more-container');
                        if (oldLoadMore) {
                            oldLoadMore.remove();
                        }
                        
                        // Add new button
                        repliesContainer.appendChild(loadMoreBtn);
                    }
                } else {
                    console.error('Error loading more replies:', data);
                    if (button && !button.isConnected) {
                        // Button was removed from DOM, create a new one
                        const loadMoreBtn = document.createElement('div');
                        loadMoreBtn.className = 'text-center mt-2 mb-2 load-more-container';
                        loadMoreBtn.innerHTML = `
                            <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                    data-parent-id="${parentId}" 
                                    data-post-id="${postId}" 
                                    data-offset="${offset}">
                                Có lỗi xảy ra. Thử lại
                            </button>
                        `;
                        repliesContainer.appendChild(loadMoreBtn);
                    } else if (button) {
                        button.innerHTML = 'Có lỗi xảy ra. Thử lại';
                        button.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (button && !button.isConnected) {
                    // Button was removed from DOM, create a new one
                    const loadMoreBtn = document.createElement('div');
                    loadMoreBtn.className = 'text-center mt-2 mb-2 load-more-container';
                    loadMoreBtn.innerHTML = `
                        <button class="btn btn-sm btn-outline-primary load-more-replies" 
                                data-parent-id="${parentId}" 
                                data-post-id="${postId}" 
                                data-offset="${offset}">
                            Có lỗi xảy ra. Thử lại
                        </button>
                    `;
                    repliesContainer.appendChild(loadMoreBtn);
                } else if (button) {
                    button.innerHTML = 'Có lỗi xảy ra. Thử lại';
                    button.disabled = false;
                }
            });
        }
    });
    
    // Initialize comment elements (textareas, buttons, etc.)
    function initializeNewCommentElements() {
        // Re-initialize auto-expanding textareas
        document.querySelectorAll('.auto-expand-textarea').forEach(textarea => {
            if (!textarea.hasListener) {
                textarea.hasListener = true;
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                    
                    const sendButton = this.parentElement.querySelector('.send-button');
                    if (sendButton) {
                        if (this.scrollHeight > 40) {
                            sendButton.classList.remove('top-0', 'mt-1');
                            sendButton.classList.add('top-50', 'translate-middle-y');
                        } else {
                            sendButton.classList.remove('top-50', 'translate-middle-y');
                            sendButton.classList.add('top-0', 'mt-1');
                        }
                    }
                });
                
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            }
        });
        
        // Re-initialize reply buttons
        document.querySelectorAll('.toggle-reply').forEach(btn => {
            if (!btn.hasListener) {
                btn.hasListener = true;
                btn.addEventListener('click', function(e) {
                    e.preventDefault(); // Ngăn hành vi mặc định
                    e.stopPropagation(); // Ngăn sự kiện lan truyền
                    
                    console.log('Toggle reply clicked for comment ID:', this.dataset.id);
                    const commentId = this.dataset.id;
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    
                    // Đóng tất cả các form trả lời khác
                    document.querySelectorAll('.reply-form:not(.d-none)').forEach(form => {
                        if (form.id !== `reply-form-${commentId}`) {
                            form.classList.add('d-none');
                        }
                    });
                    
                    if (replyForm) {
                        replyForm.classList.toggle('d-none');
                        if (!replyForm.classList.contains('d-none')) {
                            replyForm.querySelector('textarea')?.focus();
                        }
                    } else {
                        console.error(`Reply form #reply-form-${commentId} not found`);
                    }
                });
            }
        });
        
        // Re-initialize edit buttons
        document.querySelectorAll('.edit-comment').forEach(btn => {
            if (!btn.hasListener) {
                btn.hasListener = true;
                btn.addEventListener('click', function() {
                    const commentId = this.dataset.id;
                    const contentElement = document.getElementById(`content-${commentId}`);
                    const editFormElement = document.getElementById(`edit-form-${commentId}`);
                    
                    if (contentElement && editFormElement) {
                        contentElement.classList.add('d-none');
                        editFormElement.classList.remove('d-none');
                        
                        const textarea = editFormElement.querySelector('textarea');
                        if (textarea) {
                            textarea.style.height = 'auto';
                            textarea.style.height = (textarea.scrollHeight) + 'px';
                            textarea.focus();
                            
                            const textLength = textarea.value.length;
                            textarea.setSelectionRange(textLength, textLength);
                        }
                    }
                });
            }
        });
        
        // Re-initialize cancel edit buttons
        document.querySelectorAll('.cancel-edit').forEach(btn => {
            if (!btn.hasListener) {
                btn.hasListener = true;
                btn.addEventListener('click', function() {
                    const commentId = this.dataset.id;
                    const contentElement = document.getElementById(`content-${commentId}`);
                    const editFormElement = document.getElementById(`edit-form-${commentId}`);
                    
                    if (contentElement && editFormElement) {
                        contentElement.classList.remove('d-none');
                        editFormElement.classList.add('d-none');
                    }
                });
            }
        });
        
        // Re-initialize delete buttons
        document.querySelectorAll('.delete-comment').forEach(btn => {
            if (!btn.hasListener) {
                btn.hasListener = true;
                btn.addEventListener('click', function() {
                    if (confirm('Bạn có chắc muốn xóa bình luận này?')) {
                        const commentId = this.dataset.id;
                        
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '../controllers/delete_comment.php';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'comment_id';
                        input.value = commentId;
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
    }
    
    // QUAN TRỌNG: Xóa bỏ đăng ký sự kiện trùng lặp - Chỉ khởi tạo một lần
    // Các sự kiện dưới đây đã được xử lý trong hàm initializeNewCommentElements
    // Xóa bỏ đoạn code dưới đây để tránh đăng ký sự kiện trùng lặp
    /*
    // Toggle reply form
    document.querySelectorAll('.toggle-reply').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            if (replyForm) {
                replyForm.classList.toggle('d-none');
                if (!replyForm.classList.contains('d-none')) {
                    replyForm.querySelector('textarea')?.focus();
                }
            }
        });
    });
    
    // Edit comment
    document.querySelectorAll('.edit-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            const contentElement = document.getElementById(`content-${commentId}`);
            const editFormElement = document.getElementById(`edit-form-${commentId}`);
            
            if (contentElement && editFormElement) {
                contentElement.classList.add('d-none');
                editFormElement.classList.remove('d-none');
                
                // Auto-adjust height of textarea after showing the edit form
                const textarea = editFormElement.querySelector('textarea');
                if (textarea) {
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight) + 'px';
                    textarea.focus();
                    
                    // Position the cursor at the end of the text
                    const textLength = textarea.value.length;
                    textarea.setSelectionRange(textLength, textLength);
                }
            }
        });
    });
    
    // Cancel edit
    document.querySelectorAll('.cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            const contentElement = document.getElementById(`content-${commentId}`);
            const editFormElement = document.getElementById(`edit-form-${commentId}`);
            
            if (contentElement && editFormElement) {
                contentElement.classList.remove('d-none');
                editFormElement.classList.add('d-none');
            }
        });
    });
    
    // Delete comment
    document.querySelectorAll('.delete-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Bạn có chắc muốn xóa bình luận này?')) {
                const commentId = this.dataset.id;
                
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../controllers/delete_comment.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'comment_id';
                input.value = commentId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    */
    
    // Initialize all comment elements
    initializeNewCommentElements();
});