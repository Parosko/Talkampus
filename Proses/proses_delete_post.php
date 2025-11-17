<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);

    // Pastikan postingan ada dan milik user
    $cek = mysqli_query($koneksi, "SELECT * FROM posts WHERE post_id='$post_id' AND user_id='$user_id'");
    if (mysqli_num_rows($cek) === 0) {
        echo "❌ Kamu tidak berhak menghapus postingan ini.";
        exit;
    }

    // Hapus semua gambar terkait di folder dan database
    $gambar = mysqli_query($koneksi, "SELECT image_url FROM post_images WHERE post_id='$post_id'");
    while ($row = mysqli_fetch_assoc($gambar)) {
        if (file_exists("../" . $row['image_url'])) {
            unlink("../" . $row['image_url']);
        }
    }
    mysqli_query($koneksi, "DELETE FROM post_images WHERE post_id='$post_id'");

    // Hapus komentar (tabel yang benar: comments)
    mysqli_query($koneksi, "DELETE FROM comments WHERE post_id='$post_id'");

    // Hapus likes
    mysqli_query($koneksi, "DELETE FROM likes WHERE post_id='$post_id'");

    // Hapus postingan
    mysqli_query($koneksi, "DELETE FROM posts WHERE post_id='$post_id'");

    header("Location: ../beranda.php");
    exit;
} else {
    echo "❌ Akses tidak valid.";
}
?>
