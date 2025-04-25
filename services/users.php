<?php
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
?>