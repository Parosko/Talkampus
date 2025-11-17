<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Slackey&display=swap" rel="stylesheet">

<style>
.navbar {
    width: 100%;
    background: #222;
    padding: 12px 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    position: sticky;
    top: 0;
    z-index: 999;
}

/* Kiri: Logo TALKAMPUS */
.navbar .logo a {
    font-family: 'Slackey', cursive;
    color: #fff;
    font-size: 22px;
    letter-spacing: 1px;
    text-decoration: none;
}

.navbar .logo a:hover {
    color: #ffcc00; 
}

.navbar .nav-links {
    display: flex;
    gap: 30px;
}

.navbar .nav-links a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    padding: 6px 10px;
}

.navbar .nav-links a:hover {
    background: #444;
    border-radius: 6px;
}

.logout-button button {
    background-color: #ff5252;
    color: white;
    border: none;
    padding: 6px 12px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
}

.logout-button button:hover {
    background-color: #e04848;
}
</style>

<div class="navbar">
    <!-- Kiri -->
    <div class="logo">
        <a href="beranda.php">TALKAMPUS</a>
    </div>

    <!-- Tengah -->
    <div class="nav-links">
        <a href="beranda.php">🏠 Beranda</a>
        <a href="profile.php?id=<?= $_SESSION['user_id']; ?>">👤 Profil Saya</a>
        <a href="search.php">🔍 Cari Pengguna</a>
    </div>

    <!-- Kanan -->
    <form class="logout-button" action="logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
</div>
