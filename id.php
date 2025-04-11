<?php
$post_id = $_GET['id'];
$post = $conn->query("SELECT * FROM posts WHERE id = $post_id")->fetch_assoc();
?>