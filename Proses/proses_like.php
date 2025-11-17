<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_GET['post_id'];

// Cek apakah user sudah like
$cek = mysqli_query($koneksi, "SELECT * FROM likes WHERE user_id='$user_id' AND post_id='$post_id'");

if (mysqli_num_rows($cek) > 0) {
    // Jika sudah like, maka unlike
    mysqli_query($koneksi, "DELETE FROM likes WHERE user_id='$user_id' AND post_id='$post_id'");
} else {
    // Jika belum, tambahkan like baru
    mysqli_query($koneksi, "INSERT INTO likes (user_id, post_id) VALUES ('$user_id', '$post_id')");
}

header("Location: ../beranda.php");
exit;
?>
