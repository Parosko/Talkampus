<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Masuk</title>
</head>
<body>

<h2>Masuk ke Talkampus</h2>

<?php 
// ✅ Tampilkan pesan error atau informasi jika ada
if (isset($_SESSION['pesan'])) {
    echo "<p style='color:red;'>" . htmlspecialchars($_SESSION['pesan']) . "</p>";
    unset($_SESSION['pesan']);
}
?>

<!-- ✅ Arahkan ke folder Proses -->
<form action="Proses/proses_login.php" method="POST">
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Kata Sandi:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Masuk</button>
</form>

<p>Belum punya akun? <a href="daftar.php">Daftar</a></p>

</body>
</html>
