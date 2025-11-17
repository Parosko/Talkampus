<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    echo "unauthorized";
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = intval($_POST['comment_id']);
$comment_text = trim($_POST['comment_text']);

if ($comment_text === "") {
    echo "empty";
    exit;
}

// Pastikan komentar milik user ini
$check = mysqli_query($koneksi, "SELECT * FROM comments WHERE comment_id='$comment_id' AND user_id='$user_id'");
if (mysqli_num_rows($check) == 0) {
    echo "forbidden";
    exit;
}

// Update komentar
mysqli_query($koneksi, "UPDATE comments SET comment_text='$comment_text' WHERE comment_id='$comment_id'");
echo "ok";
?>
