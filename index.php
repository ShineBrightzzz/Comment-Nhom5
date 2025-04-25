<?php 
include 'config/config.php';
include './layouts/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Facebook-style Posts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .post-card {
      border: 1px solid #dee2e6;
      border-radius: 10px;
      margin-bottom: 20px;
      padding: 15px;
      transition: box-shadow 0.2s ease;
      background-color: #fff;
    }
    .post-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .post-image {
      width: 100%;
      max-height: 300px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    .post-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .post-text {
      font-size: 1rem;
      color: #333;
    }
    a.card-link {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>
<body>
  <div class="container my-5">
    <h2 class="mb-4">Bài viết mới nhất</h2>

    <?php
    $sql = "SELECT * FROM post";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($post = $result->fetch_assoc()) {
            $image = !empty($post['image']) ? $post['image'] : null;
            ?>
            <a href="/Comment-Nhom5/posts/<?php echo $post['id']; ?>" class="card-link">
              <div class="post-card">
                <?php if ($image): ?>
                  <img src="<?php echo htmlspecialchars($image); ?>" alt="Post Image" class="post-image" />
                <?php endif; ?>
                <div>
                  <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                  <div class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                  <small class="text-muted">Đăng lúc: <?php echo $post['created_at']; ?></small>
                </div>
              </div>
            </a>
            <?php
        }
    } else {
        echo "<p class='alert alert-info'>Không có bài viết nào</p>";
    }

    $conn->close();
    ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
