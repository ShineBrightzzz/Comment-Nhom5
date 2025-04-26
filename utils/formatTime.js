function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    const intervals = [{
            label: 'năm',
            seconds: 31536000
        },
        {
            label: 'tháng',
            seconds: 2592000
        },
        {
            label: 'ngày',
            seconds: 86400
        },
        {
            label: 'giờ',
            seconds: 3600
        },
        {
            label: 'phút',
            seconds: 60
        },
        {
            label: 'giây',
            seconds: 1
        }
    ];
    for (let i = 0; i < intervals.length; i++) {
        const interval = intervals[i];
        const count = Math.floor(seconds / interval.seconds);
        if (count >= 1) {
            return `${count} ${interval.label} trước`;
        }
    }
    return 'vừa xong';
}

function updateTimes() {
    const elements = document.querySelectorAll('.time-elapsed');
    elements.forEach(el => {
        const time = el.getAttribute('data-time');
        el.textContent = timeAgo(time);
    });
}

// Cập nhật thời gian ngay khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    updateTimes();
    
    // Cập nhật thời gian liên tục mỗi 60 giây mà không cần reload trang
    setInterval(updateTimes, 60000);
});