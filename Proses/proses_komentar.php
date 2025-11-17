<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$comment_text = mysqli_real_escape_string($koneksi, $_POST['comment_text']);

if (!empty(trim($comment_text))) {
    mysqli_query($koneksi, "INSERT INTO comments (post_id, user_id, comment_text) VALUES ('$post_id', '$user_id', '$comment_text')");
}

header("Location: ../detail_post.php?id=$post_id");
exit;
?>
