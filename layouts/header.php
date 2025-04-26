<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- User data for notifications -->
  <?php if (isset($_SESSION['user_id'])): ?>
  <meta name="user-id" content="<?php echo $_SESSION['user_id']; ?>">
  <?php endif; ?>
  
  <!-- Pusher configuration -->
  <meta name="pusher-key" content="<?php echo $_ENV['PUSHER_APP_KEY'] ?? ''; ?>">
  <meta name="pusher-cluster" content="<?php echo $_ENV['PUSHER_APP_CLUSTER'] ?? 'ap1'; ?>">
  
  <title>Chi tiết bài viết</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/Comment-Nhom5/css/style.css">
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <!-- Pusher JavaScript library -->
  <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
  
  <!-- Notification sound -->
  <audio id="notification-sound" preload="auto">
      <source src="/Comment-Nhom5/assets/notification.mp3" type="audio/mpeg">
  </audio>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="../">Trang chủ</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="/Comment-Nhom5/logout">Đăng xuất</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link text-white" href="/Comment-Nhom5/login">Đăng nhập</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="/Comment-Nhom5/register">Đăng ký</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>