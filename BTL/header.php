<?php session_start(); ?>
<nav>
    <a href="index.php">Trang chủ</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Chào, <?php echo $_SESSION['user_name']; ?>!</span>
        <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
        <a href="login.php">Đăng nhập</a>
        <a href="register.php">Đăng ký</a>
    <?php endif; ?>
</nav>
<hr>
