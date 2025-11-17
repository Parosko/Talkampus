<?php
session_start();
include "../koneksi.php"; // ✅ path diperbaiki

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // ✅ keluar dari folder Proses
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;
$redirect = isset($_GET['redirect']) ? "../" . $_GET['redirect'] : "../beranda.php"; // ✅ tambahkan ../

if ($comment_id <= 0) {
    header("Location: $redirect");
    exit;
}

// Ambil owner info
$q = mysqli_query($koneksi, "
    SELECT comments.user_id AS comment_owner, posts.user_id AS post_owner
    FROM comments
    JOIN posts ON comments.post_id = posts.post_id
    WHERE comments.comment_id = $comment_id
");
if (!$q || mysqli_num_rows($q) == 0) {
    header("Location: $redirect");
    exit;
}
$row = mysqli_fetch_assoc($q);
$comment_owner = $row['comment_owner'];
$post_owner = $row['post_owner'];

if ($user_id != $comment_owner && $user_id != $post_owner) {
    header("Location: $redirect");
    exit;
}

// Hapus komentar
mysqli_query($koneksi, "DELETE FROM comments WHERE comment_id = $comment_id");

header("Location: $redirect");
exit;
?>
