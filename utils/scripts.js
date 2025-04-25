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
    
    // Toggle reply form
    document.querySelectorAll('.toggle-reply').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            replyForm.classList.toggle('d-none');
            if (!replyForm.classList.contains('d-none')) {
                replyForm.querySelector('textarea').focus();
            }
        });
    });
    
    // Edit comment
    document.querySelectorAll('.edit-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            document.getElementById(`content-${commentId}`).classList.add('d-none');
            document.getElementById(`edit-form-${commentId}`).classList.remove('d-none');
            
            // Auto-adjust height of textarea after showing the edit form
            const textarea = document.getElementById(`edit-form-${commentId}`).querySelector('textarea');
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            textarea.focus();
            
            // Position the cursor at the end of the text
            const textLength = textarea.value.length;
            textarea.setSelectionRange(textLength, textLength);
        });
    });
    
    // Cancel edit
    document.querySelectorAll('.cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.id;
            document.getElementById(`content-${commentId}`).classList.remove('d-none');
            document.getElementById(`edit-form-${commentId}`).classList.add('d-none');
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
});