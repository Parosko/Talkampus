<?php
session_start();
include "../koneksi.php";

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo 'unauthorized';
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
if ($comment_id <= 0) {
    echo 'invalid';
    exit;
}

// Ambil komentar + info post owner
$q = mysqli_query($koneksi, "
    SELECT comments.user_id AS comment_owner, posts.user_id AS post_owner
    FROM comments
    JOIN posts ON comments.post_id = posts.post_id
    WHERE comments.comment_id = $comment_id
");
if (!$q || mysqli_num_rows($q) == 0) {
    echo 'not_found';
    exit;
}
$row = mysqli_fetch_assoc($q);
$comment_owner = $row['comment_owner'];
$post_owner = $row['post_owner'];

// Cek izin: pemilik komentar atau pemilik post boleh hapus
if ($user_id != $comment_owner && $user_id != $post_owner) {
    echo 'forbidden';
    exit;
}

// Lakukan penghapusan
$del = mysqli_query($koneksi, "DELETE FROM comments WHERE comment_id = $comment_id");
if ($del) {
    echo 'ok';
} else {
    echo 'error';
}
?>
