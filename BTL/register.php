<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = uniqid(); // Tạo ID ngẫu nhiên
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = "default-avatar.png"; // Ảnh đại diện mặc định

    // Kiểm tra email đã tồn tại chưa
    $check_query = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "Email đã được sử dụng!";
    } else {
        // Chèn người dùng mới vào database
        $query = $conn->prepare("INSERT INTO user (id, name, email, password, avatar) VALUES (?, ?, ?, ?, ?)");
        $query->bind_param("sssss", $id, $name, $email, $password, $avatar);
        if ($query->execute()) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            header("Location: index.php");
            exit();
        } else {
            echo "Lỗi đăng ký!";
        }
    }
}
?>

<h2>Đăng ký</h2>
<form method="post">
    Tên: <input type="text" name="name" required><br>
    Email: <input type="email" name="email" required><br>
    Mật khẩu: <input type="password" name="password" required><br>
    <button type="submit">Đăng ký</button>
</form>
<p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>