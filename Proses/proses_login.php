<?php
session_start();
include "../koneksi.php";

$email = $_POST['email'];
$password = $_POST['password'];

$data = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
$user = mysqli_fetch_assoc($data);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];

    header("Location: ../beranda.php");
} else {
    $_SESSION['pesan'] = "Email atau kata sandi salah!";
    header("Location: ../login.php");
}
?>
