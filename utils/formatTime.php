<?php
function timeAgo($datetime) {
    date_default_timezone_set('Asia/Ho_Chi_Minh'); 

    $time = strtotime($datetime);
    if (!$time) return 'Thời gian không hợp lệ';

    $now = time();
    $diff = $now - $time;

    if ($diff < 0) {
        return date('d/m/Y H:i', $time);
    }
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } else {
        return date('d/m/Y H:i', $time);
    }
}
?>