<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_GET['post_id'] ?? null;

if (!$post_id) {
    echo "invalid";
    exit;
}

// Cek apakah user sudah like
$cek = mysqli_query($koneksi, "SELECT * FROM likes WHERE post_id=$post_id AND user_id=$user_id");

if (mysqli_num_rows($cek) > 0) {
    // Kalau sudah like → hapus (unlike)
    mysqli_query($koneksi, "DELETE FROM likes WHERE post_id=$post_id AND user_id=$user_id");
    echo "unliked";
} else {
    // Kalau belum → tambahkan like
    mysqli_query($koneksi, "INSERT INTO likes (post_id, user_id) VALUES ($post_id, $user_id)");
    echo "liked";
}
?>
