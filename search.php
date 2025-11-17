<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$keyword = isset($_GET['q']) ? trim($_GET['q']) : "";
$results = [];

if ($keyword !== "") {
    $q = mysqli_query($koneksi, "
        SELECT user_id, username, profile_picture 
        FROM users 
        WHERE username LIKE '%$keyword%'
        LIMIT 20
    ");

    while ($row = mysqli_fetch_assoc($q)) {
        $results[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    
    <link rel="stylesheet" href="Styles/global_style.css">
    <title>Cari Pengguna</title>

    <style>
    body { font-family: Arial; background:#f5f5f5; }

    .search-box {
        max-width: 500px;
        margin: 20px auto;
        text-align: center;
    }

    .search-result {
        max-width: 500px;
        margin: 10px auto;
        background: white;
        padding: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .search-result img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    a { text-decoration: none; color: black; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<?php include "Components/navbar.php"; ?>


<div class="search-box">
    <h2>Cari Pengguna</h2>

    <form method="GET">
        <input type="text" name="q" placeholder="Masukkan username..." 
               value="<?= htmlspecialchars($keyword) ?>"
               style="padding:8px; width:60%; font-size:16px;">
        <button type="submit">Cari</button>
    </form>
</div>

<?php if ($keyword !== "") : ?>

    <h3 style="text-align:center;">Hasil Pencarian:</h3>

    <?php if (empty($results)) : ?>
        <p style="text-align:center;">Tidak ada pengguna ditemukan.</p>
    <?php endif; ?>

    <?php foreach ($results as $u) : ?>
        <a href="profile.php?id=<?= $u['user_id'] ?>">
            <div class="search-result">
                <img src="<?= $u['profile_picture'] ?: 'default_pp.png' ?>">
                <b>@<?= htmlspecialchars($u['username']) ?></b>
            </div>
        </a>
    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
