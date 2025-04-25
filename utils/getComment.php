<?php

function getComments($conn, $post_id, $parent_id = NULL) {
    $query = $conn->prepare("SELECT * FROM comment WHERE post_id = ? AND parent_id <=> ? ORDER BY created_at DESC");
    $query->bind_param("ss", $post_id, $parent_id);
    $query->execute();
    return $query->get_result();
}

?>