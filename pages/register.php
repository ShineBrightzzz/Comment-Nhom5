<?php
session_start();
include '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = uniqid(); // Tạo ID ngẫu nhiên
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = trim("https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random"); // Sử dụng URL mặc định nếu không nhập
    
    // Kiểm tra email đã tồn tại chưa
    $check_query = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        $error = "Email đã được sử dụng!";
    } else {
        // Chèn người dùng mới vào database
        $query = $conn->prepare("INSERT INTO user (id, username, name, email, password, avatar) VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("sssss", $id, $username, $name, $email, $password, $avatar);
        if ($query->execute()) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            header("Location: /Comment-Nhom5/login");
            exit();
        } else {
            $error = "Lỗi đăng ký!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h2 class="mb-4 text-center">Đăng ký</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Tên:</label>
                <input type="text" name="username" class="form-control" id="username" required>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Tên:</label>
                <input type="text" name="name" class="form-control" id="name" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" id="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu:</label>
                <input type="password" name="password" class="form-control" id="password" required>
            </div>


            <button type="submit" class="btn btn-success w-100">Đăng ký</button>
        </form>

        <div class="text-center mt-3">
            <p>Đã có tài khoản? <a href="/Comment-Nhom5/login">Đăng nhập</a></p>
        </div>
    </div>
</div>

</body>
</html>
