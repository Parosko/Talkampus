<?php
session_start();
include "../koneksi.php";

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Cek apakah email sudah dipakai
$cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
if (mysqli_num_rows($cek) > 0) {
    $_SESSION['pesan'] = "Email sudah digunakan, silakan pakai yang lain.";
    header("Location: ../daftar.php");
    exit;
}

// Simpan ke database
$query = mysqli_query($koneksi, "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$password')");

if ($query) {
    $_SESSION['pesan'] = "Pendaftaran berhasil! Silakan masuk.";
    header("Location: ../login.php");
} else {
    $_SESSION['pesan'] = "Terjadi kesalahan saat mendaftar.";
    header("Location: ../daftar.php");
}
?>
