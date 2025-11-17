<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun</title>
</head>
<body>

<h2>Daftar Akun Baru</h2>

<?php 
// ✅ Tampilkan pesan error atau sukses jika ada
if (isset($_SESSION['pesan'])) {
    echo "<p style='color:red;'>" . htmlspecialchars($_SESSION['pesan']) . "</p>";
    unset($_SESSION['pesan']);
}
?>

<!-- ✅ Arahkan ke folder "Proses" -->
<form action="Proses/proses_daftar.php" method="POST">
    <label>Nama Pengguna:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Kata Sandi:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Daftar</button>
</form>

<p>Sudah punya akun? <a href="login.php">Masuk</a></p>

</body>
</html>
