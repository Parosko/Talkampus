<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "talkampus";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Gagal terhubung ke database: " . mysqli_connect_error());
}
?>
